<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\AnnouncementModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Mobile API for announcements — mirrors web {@see \App\Controllers\Announcements} permissions and store logic.
 */
class Announcements extends BaseController
{
    protected AnnouncementModel $announcements;
    protected UserModel $users;

    public function __construct()
    {
        $this->announcements = new AnnouncementModel();
        $this->users        = new UserModel();
        helper(['text']);
    }

    /**
     * GET api/announcements
     * Lists announcements (same visibility rules as web index) plus create-form metadata for allowed roles.
     */
    public function index(): ResponseInterface
    {
        $userId = (int) (session()->get('user_id') ?? 0);
        if ($userId < 1) {
            return $this->response->setStatusCode(401)->setJSON(['announcements' => []]);
        }

        $role = strtoupper((string) (session()->get('role') ?? ''));
        if ($role === 'STUDENT') {
            $db = \Config\Database::connect();
            $rows = $db->table('announcements a')
                ->select('a.id, a.title, a.body, a.created_at, a.audience_type, u.name AS created_by_name')
                ->join('notifications n', 'n.reference_id = a.id AND n.reference_table = "announcements" AND n.type = "announcement"', 'inner')
                ->join('users u', 'u.id = a.created_by', 'left')
                ->where('n.user_id', $userId)
                ->where('a.deleted_at', null)
                ->groupBy('a.id')
                ->orderBy('a.created_at', 'DESC')
                ->get()
                ->getResultArray();
        } else {
            $rows = $this->announcements
                ->select('announcements.id, announcements.title, announcements.body, announcements.created_at, announcements.audience_type, users.name AS created_by_name')
                ->join('users', 'users.id = announcements.created_by', 'left')
                ->where('announcements.deleted_at', null)
                ->orderBy('announcements.created_at', 'DESC')
                ->findAll();
        }

        $canManage = $this->canManageAnnouncements();
        $audience  = ($canManage && in_array($role, ['PRINCIPAL', 'ANNOUNCER', 'ADMIN'], true))
            ? $this->audienceOptionsForRole($role)
            : [];
        $teacherSections = ($canManage && $role === 'TEACHER')
            ? $this->buildTeacherSectionsForTeacher($userId)
            : [];

        return $this->response->setJSON([
            'announcements'      => array_values($rows),
            'can_manage'         => $canManage,
            'role'               => $role,
            'audience_options'   => (object) $audience,
            'teacher_sections'   => $teacherSections,
        ]);
    }

    /**
     * POST api/announcements (JSON)
     * Same rules as web {@see \App\Controllers\Announcements::store()}.
     */
    public function store(): ResponseInterface
    {
        if (! $this->canManageAnnouncements()) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'You do not have permission to create announcements.',
            ]);
        }

        $userId = (int) (session()->get('user_id') ?? 0);
        $role   = strtoupper((string) (session()->get('role') ?? ''));

        $json = null;
        try {
            $json = $this->request->getJSON(true);
        } catch (\Throwable) {
            $json = null;
        }
        if (is_array($json)) {
            $title = trim((string) ($json['title'] ?? ''));
            $body  = trim((string) ($json['body'] ?? ''));
            $sectionIdPost = isset($json['section_id']) ? (int) $json['section_id'] : null;
            $audiencePost  = isset($json['audience_type']) ? strtolower(trim((string) $json['audience_type'])) : null;
        } else {
            $title = trim((string) $this->request->getPost('title'));
            $body  = trim((string) $this->request->getPost('body'));
            $sectionIdPost = $this->request->getPost('section_id') !== null && $this->request->getPost('section_id') !== ''
                ? (int) $this->request->getPost('section_id')
                : null;
            $audiencePost = $this->request->getPost('audience_type')
                ? strtolower(trim((string) $this->request->getPost('audience_type')))
                : null;
        }

        if ($title === '' || $body === '') {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'Title and body are required.',
            ]);
        }

        $sectionId    = null;
        $audienceType = 'school-wide';

        if ($role === 'TEACHER') {
            $chosenSectionId = (int) ($sectionIdPost ?? 0);
            if ($chosenSectionId < 1) {
                return $this->response->setStatusCode(400)->setJSON([
                    'status'  => 'error',
                    'message' => 'Please choose a section to announce to.',
                ]);
            }

            $db = \Config\Database::connect();
            $isAssigned = $db->table('teacher_sections')
                ->where('teacher_id', $userId)
                ->where('section_id', $chosenSectionId)
                ->where('status', 'accepted')
                ->countAllResults() > 0;

            if (! $isAssigned) {
                return $this->response->setStatusCode(403)->setJSON([
                    'status'  => 'error',
                    'message' => 'You can only announce to sections assigned to you.',
                ]);
            }

            $sectionId    = $chosenSectionId;
            $audienceType = 'section';
        } elseif (in_array($role, ['PRINCIPAL', 'ANNOUNCER', 'ADMIN'], true)) {
            $audienceType = $audiencePost ?? 'school-wide';
            $allowed      = ['school-wide', 'teachers-only', 'staff-only', 'students-only'];
            if (! in_array($audienceType, $allowed, true)) {
                return $this->response->setStatusCode(400)->setJSON([
                    'status'  => 'error',
                    'message' => 'Please choose a valid audience.',
                ]);
            }
        }

        $this->announcements->insert([
            'title'          => $title,
            'body'           => $body,
            'created_by'     => $userId,
            'status'         => 'ACTIVE',
            'audience_type'  => $audienceType,
            'section_id'     => $sectionId,
        ]);

        $id = $this->announcements->getInsertID();
        $titleShort   = character_limiter($title, 60);
        $notifMessage = 'Announce message for you: ' . $titleShort;
        $db           = \Config\Database::connect();

        if ($role === 'TEACHER') {
            $userIds = $db->table('users u')
                ->select('u.id')
                ->distinct()
                ->join('teacher_sections ts', 'ts.section_id = u.section_id', 'inner')
                ->where('ts.teacher_id', $userId)
                ->where('ts.status', 'accepted')
                ->where('u.section_id', (int) $sectionId)
                ->where('u.role', 'STUDENT')
                ->where('u.is_active', 1)
                ->get()
                ->getResultArray();
        } else {
            $userIds = $this->resolveRecipientsByAudience($audienceType, $role);
        }

        if ($userIds === []) {
            return $this->response->setJSON([
                'status'  => 'success',
                'message' => 'Announcement published. No student recipients found for your assigned sections.',
            ]);
        }

        $now = date('Y-m-d H:i:s');
        foreach ($userIds as $row) {
            $db->table('notifications')->insert([
                'user_id'         => (int) $row['id'],
                'type'            => 'announcement',
                'reference_table' => 'announcements',
                'reference_id'    => $id,
                'message'         => $notifMessage,
                'is_read'         => 0,
                'created_at'      => $now,
            ]);
        }

        if ($role === 'TEACHER') {
            return $this->response->setJSON([
                'status'  => 'success',
                'message' => 'Announcement published. Your students have been notified.',
            ]);
        }

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Announcement published.',
        ]);
    }

    private function canManageAnnouncements(): bool
    {
        $role = strtoupper((string) (session()->get('role') ?? ''));

        return in_array($role, ['ADMIN', 'PRINCIPAL', 'ANNOUNCER', 'TEACHER'], true);
    }

    /**
     * Same audience labels as web {@see \App\Controllers\Announcements::audienceOptionsForRole()}.
     */
    private function audienceOptionsForRole(string $role): array
    {
        if (! in_array($role, ['PRINCIPAL', 'ANNOUNCER', 'ADMIN'], true)) {
            return [];
        }

        return [
            'school-wide'   => 'For all users',
            'teachers-only' => 'For teachers only',
            'staff-only'    => 'For staffs only',
            'students-only' => 'For students only',
        ];
    }

    /**
     * Same section list as web {@see \App\Controllers\Announcements::create()} for teachers.
     *
     * @return list<array{id:int, display_label:string}>
     */
    private function buildTeacherSectionsForTeacher(int $teacherId): array
    {
        $db = \Config\Database::connect();
        $rows = $db->table('teacher_sections ts')
            ->select('s.id as section_id, s.name, s.grade_level, s.class_schedule, ts.assignment_role, ts.subject_name')
            ->join('sections s', 's.id = ts.section_id', 'inner')
            ->where('ts.teacher_id', $teacherId)
            ->where('ts.status', 'accepted')
            ->orderBy('s.grade_level', 'ASC')
            ->orderBy('s.name', 'ASC')
            ->get()
            ->getResultArray();

        $grouped = [];
        foreach ($rows as $row) {
            $sid = (int) ($row['section_id'] ?? 0);
            if ($sid < 1) {
                continue;
            }
            if (! isset($grouped[$sid])) {
                $grouped[$sid] = [
                    'id'             => $sid,
                    'name'           => (string) ($row['name'] ?? ''),
                    'grade_level'    => (string) ($row['grade_level'] ?? ''),
                    'class_schedule' => (string) ($row['class_schedule'] ?? ''),
                    'subjects'       => [],
                ];
            }
            $assignmentRole = strtoupper((string) ($row['assignment_role'] ?? 'ADVISER'));
            if ($assignmentRole === 'SUBJECT_TEACHER') {
                $subjectName = trim((string) ($row['subject_name'] ?? ''));
                if ($subjectName !== '') {
                    $grouped[$sid]['subjects'][] = $subjectName;
                }
            } else {
                $rawSchedule = (string) ($grouped[$sid]['class_schedule'] ?? '');
                if ($rawSchedule !== '') {
                    $decoded = json_decode($rawSchedule, true);
                    if (is_array($decoded) && ! empty($decoded['slots']) && is_array($decoded['slots'])) {
                        foreach ($decoded['slots'] as $slot) {
                            if (empty($slot['adviser_teaches'])) {
                                continue;
                            }
                            $adviserSubject = trim((string) ($slot['subject'] ?? ''));
                            if ($adviserSubject !== '') {
                                $grouped[$sid]['subjects'][] = $adviserSubject;
                            }
                        }
                    }
                }
            }
        }

        $out = [];
        foreach ($grouped as $section) {
            $subjects = array_values(array_unique($section['subjects']));
            $label    = $section['name'] !== '' ? $section['name'] : ('Section #' . $section['id']);
            if ($subjects !== []) {
                $label .= ' (' . implode(', ', $subjects) . ')';
            }
            $out[] = [
                'id'             => (int) $section['id'],
                'display_label' => $label,
            ];
        }

        return $out;
    }

    private function resolveRecipientsByAudience(string $audienceType, string $actorRole): array
    {
        $db = \Config\Database::connect();

        $fetchByRoles = static function (array $roles) use ($db): array {
            if ($roles === []) {
                return [];
            }

            return $db->table('users')
                ->select('id')
                ->where('is_active', 1)
                ->whereIn('role', $roles)
                ->get()
                ->getResultArray();
        };

        if ($audienceType === 'teachers-only') {
            $rows = $fetchByRoles(['TEACHER']);
            if ($actorRole === 'ANNOUNCER') {
                $rows = array_merge($rows, $fetchByRoles(['PRINCIPAL']));
            }

            return $this->uniqueRecipientRows($rows);
        }

        if ($audienceType === 'staff-only') {
            return $fetchByRoles(['TEACHER', 'GUIDANCE', 'PRINCIPAL', 'ANNOUNCER', 'ADMIN']);
        }

        if ($audienceType === 'students-only') {
            $studentRows = $fetchByRoles(['STUDENT']);
            $rows        = $studentRows;

            if (in_array($actorRole, ['ANNOUNCER', 'PRINCIPAL'], true)) {
                $sectionIds = $db->table('users')
                    ->select('section_id')
                    ->where('role', 'STUDENT')
                    ->where('is_active', 1)
                    ->where('section_id IS NOT NULL', null, false)
                    ->where('section_id !=', 0)
                    ->groupBy('section_id')
                    ->get()
                    ->getResultArray();

                $sectionIds = array_values(array_unique(array_map(static fn (array $r): int => (int) ($r['section_id'] ?? 0), $sectionIds)));
                $sectionIds = array_values(array_filter($sectionIds, static fn (int $id): bool => $id > 0));

                if ($sectionIds !== []) {
                    $adviserRows = $db->table('teacher_sections')
                        ->select('teacher_id AS id')
                        ->where('status', 'accepted')
                        ->whereIn('section_id', $sectionIds)
                        ->groupStart()
                            ->where('assignment_role', 'ADVISER')
                            ->orWhere('assignment_role', null)
                        ->groupEnd()
                        ->get()
                        ->getResultArray();
                    $rows = array_merge($rows, $adviserRows);
                }
            }

            return $this->uniqueRecipientRows($rows);
        }

        return $db->table('users')
            ->select('id')
            ->where('is_active', 1)
            ->get()
            ->getResultArray();
    }

    private function uniqueRecipientRows(array $rows): array
    {
        $map = [];
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id > 0) {
                $map[$id] = ['id' => $id];
            }
        }

        return array_values($map);
    }
}
