<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Libraries\AdminPrivilege;
use App\Libraries\DataEncryptor;
use App\Libraries\JwtAuth;
use App\Libraries\SecureHash;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class AdminPrivilegeTest extends CIUnitTestCase
{
    // ─── AdminPrivilege tests ───

    public function testNormalizeStripsUnknownKeys(): void
    {
        $json = json_encode(['dashboard', 'invalid_key', 'announcements'], JSON_THROW_ON_ERROR);
        $out  = AdminPrivilege::normalize($json);
        $this->assertSame(['dashboard', 'announcements'], $out);
    }

    public function testNormalizeForRoleTeacherFiltersPrivileges(): void
    {
        $json = json_encode(['dashboard', 'user_management'], JSON_THROW_ON_ERROR);
        $out  = AdminPrivilege::normalizeForRole('TEACHER', $json);
        $this->assertSame(['dashboard'], $out);
    }

    public function testCanAccessStudentUsesRoleDefaultsWhenPrivilegesEmpty(): void
    {
        $this->assertTrue(AdminPrivilege::canAccess('STUDENT', [], 'announcements'));
        $this->assertFalse(AdminPrivilege::canAccess('STUDENT', [], 'user_management'));
    }

    public function testCanAccessAdminEmptyPrivilegesMeansFullAccessMarker(): void
    {
        $this->assertTrue(AdminPrivilege::canAccess('ADMIN', [], 'user_management'));
        $this->assertTrue(AdminPrivilege::canAccess('SUPER_ADMIN', [], 'security_logs'));
    }

    public function testCanAccessPrincipalExplicitPrivileges(): void
    {
        $p = json_encode(['announcements', 'records'], JSON_THROW_ON_ERROR);
        $this->assertTrue(AdminPrivilege::canAccess('PRINCIPAL', $p, 'records'));
        $this->assertFalse(AdminPrivilege::canAccess('PRINCIPAL', $p, 'user_management'));
    }

    public function testEffectiveForRoleUnknownRoleReturnsEmptyNormalize(): void
    {
        $g = AdminPrivilege::effectiveForRole('UNKNOWN', []);
        $this->assertSame([], $g);
    }

    // ─── CRUD-level permission tests ───

    public function testCanAccessWithActionAdminImplicitlyGranted(): void
    {
        $this->assertTrue(AdminPrivilege::canAccess('ADMIN', [], 'user_management:delete'));
        $this->assertTrue(AdminPrivilege::canAccess('SUPER_ADMIN', [], 'records:create'));
    }

    public function testCanAccessWithActionFeatureNotGrantedReturnsFalse(): void
    {
        $p = json_encode(['dashboard', 'announcements'], JSON_THROW_ON_ERROR);
        $this->assertFalse(AdminPrivilege::canAccess('TEACHER', $p, 'user_management:read'));
    }

    public function testCrudFeaturesReturnsArray(): void
    {
        $features = AdminPrivilege::crudFeatures();
        $this->assertContains('user_management', $features);
        $this->assertContains('records', $features);
    }

    public function testCrudActionsHasStandardSet(): void
    {
        $actions = AdminPrivilege::crudActions();
        $this->assertSame(['read', 'create', 'update', 'delete'], $actions);
    }

    // ─── DataEncryptor tests ───

    public function testEncryptDecryptRoundTrip(): void
    {
        $plain = 'Juan Dela Cruz';
        $cipher = DataEncryptor::encrypt($plain);

        $this->assertNotNull($cipher);
        $this->assertStringStartsWith('ENC:', $cipher);
        $this->assertNotSame($plain, $cipher);
        $this->assertSame($plain, DataEncryptor::decrypt($cipher));
    }

    public function testEncryptNullReturnsNull(): void
    {
        $this->assertNull(DataEncryptor::encrypt(null));
        $this->assertNull(DataEncryptor::decrypt(null));
    }

    public function testEncryptEmptyReturnsEmpty(): void
    {
        $this->assertSame('', DataEncryptor::encrypt(''));
        $this->assertSame('', DataEncryptor::decrypt(''));
    }

    public function testDecryptPlaintextReturnsSameString(): void
    {
        $this->assertSame('hello', DataEncryptor::decrypt('hello'));
    }

    public function testEncryptFieldsSelectivelyEncrypts(): void
    {
        $data = [
            'name'            => 'Juan',
            'guardian_name'   => 'Maria',
            'guardian_contact' => '09171234567',
            'address'         => '123 Rizal St',
        ];
        $encrypted = DataEncryptor::encryptFields($data, DataEncryptor::sensitiveUserFields());

        $this->assertSame('Juan', $encrypted['name']);
        $this->assertStringStartsWith('ENC:', $encrypted['guardian_name']);
        $this->assertStringStartsWith('ENC:', $encrypted['guardian_contact']);
        $this->assertStringStartsWith('ENC:', $encrypted['address']);

        $decrypted = DataEncryptor::decryptFields($encrypted, DataEncryptor::sensitiveUserFields());
        $this->assertSame('Maria', $decrypted['guardian_name']);
        $this->assertSame('09171234567', $decrypted['guardian_contact']);
        $this->assertSame('123 Rizal St', $decrypted['address']);
    }

    public function testSensitiveUserFieldsDoesNotIncludeEmail(): void
    {
        $fields = DataEncryptor::sensitiveUserFields();
        $this->assertNotContains('email', $fields);
        $this->assertContains('guardian_name', $fields);
        $this->assertContains('address', $fields);
    }

    // ─── SecureHash tests ───

    public function testSecureHashMakeProducesValidHash(): void
    {
        $hash = SecureHash::make('testPassword123');
        $this->assertNotEmpty($hash);
        $this->assertTrue(password_verify('testPassword123', $hash));
        $this->assertFalse(password_verify('wrongPassword', $hash));
    }

    public function testSecureHashAlgorithmNameReturnsBcryptOrArgon2id(): void
    {
        $name = SecureHash::algorithmName();
        $this->assertContains($name, ['bcrypt', 'Argon2id']);
    }

    // ─── JwtAuth tests ───

    public function testJwtEncodeDecodeRoundTrip(): void
    {
        $user = ['id' => 42, 'username' => 'admin_test', 'role' => 'ADMIN'];
        $token = JwtAuth::encode($user, 60);

        $this->assertNotEmpty($token);
        $this->assertCount(3, explode('.', $token));

        $payload = JwtAuth::decode($token);
        $this->assertNotNull($payload);
        $this->assertSame(42, $payload['sub']);
        $this->assertSame('admin_test', $payload['username']);
        $this->assertSame('ADMIN', $payload['role']);
    }

    public function testJwtDecodeExpiredTokenReturnsNull(): void
    {
        $user = ['id' => 1, 'username' => 'user1', 'role' => 'STUDENT'];
        $token = JwtAuth::encode($user, -10);

        $this->assertNull(JwtAuth::decode($token));
    }

    public function testJwtDecodeTamperedTokenReturnsNull(): void
    {
        $user = ['id' => 1, 'username' => 'user1', 'role' => 'STUDENT'];
        $token = JwtAuth::encode($user, 60);
        $tampered = $token . 'tampered';

        $this->assertNull(JwtAuth::decode($tampered));
    }
}
