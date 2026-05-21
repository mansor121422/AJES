<?php

namespace App\Controllers;

use App\Libraries\AcademicYearManager;
use App\Libraries\DataEncryptor;
use App\Libraries\GradeLevel;
use App\Libraries\SectionEnrollment;
use App\Libraries\StudentEnrollmentType;
use App\Models\SectionModel;
use App\Models\UserModel;

class StudentsLog extends BaseController
{
    protected UserModel $users;
    protected SectionModel $sections;

    public function __construct()
    {
        $this->users    = new UserModel();
        $this->sections = new SectionModel();
        helper(['url', 'form']);
    }

    public function index(): string
    {
        $keyword = trim((string) $this->request->getGet('q'));
        $grade   = trim((string) $this->request->getGet('grade'));

        $builder = $this->users
            ->whereIn('role', SectionEnrollment::studentRoleSlugs())
            ->orderBy('surname', 'ASC')
            ->orderBy('first_name', 'ASC')
            ->orderBy('name', 'ASC');

        if ($grade !== '' && in_array($grade, ['1', '2', '3', '4', '5', '6'], true)) {
            $builder->where('grade_level', $grade);
        }

        $students = $builder->findAll();
        $sectionById = $this->sectionLabelsById();

        $prepared = [];
        foreach ($students as $student) {
            $prepared[] = $this->prepareStudentRow($student, $sectionById);
        }

        if ($keyword !== '') {
            $needle = strtolower($keyword);
            $prepared = array_values(array_filter($prepared, static function (array $row) use ($needle): bool {
                $haystack = strtolower(implode(' ', [
                    (string) ($row['display_name'] ?? ''),
                    (string) ($row['student_id'] ?? ''),
                    (string) ($row['username'] ?? ''),
                    (string) ($row['email'] ?? ''),
                    (string) ($row['guardian_name'] ?? ''),
                    (string) ($row['guardian_contact'] ?? ''),
                    (string) ($row['student_type_label'] ?? ''),
                    (string) ($row['previous_school'] ?? ''),
                ]));

                return str_contains($haystack, $needle);
            }));
        }

        $activeCount = 0;
        foreach ($prepared as $row) {
            if (! empty($row['is_active'])) {
                $activeCount++;
            }
        }

        return view('Admin/StudentsLog/index', [
            'students'     => $prepared,
            'keyword'      => $keyword,
            'grade'        => $grade,
            'total_count'  => count($prepared),
            'active_count' => $activeCount,
            'active_year'  => AcademicYearManager::getActive(),
            'role'         => session()->get('role') ?? 'ADMIN',
            'name'         => session()->get('name') ?? 'User',
        ]);
    }

    /** @return array<int, string> */
    private function sectionLabelsById(): array
    {
        $out = [];
        $ayId = AcademicYearManager::ensureActiveYear();
        $sectionRows = $this->sections
            ->groupStart()
                ->where('academic_year_id', $ayId)
                ->orWhere('academic_year_id', null)
            ->groupEnd()
            ->orderBy('grade_level')
            ->orderBy('name')
            ->findAll();
        foreach ($sectionRows as $section) {
            $id   = (int) ($section['id'] ?? 0);
            $name = trim((string) ($section['name'] ?? ''));
            $out[$id] = $name !== '' ? $name : ('Section #' . $id);
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $student
     * @param array<int, string> $sectionById
     * @return array<string, mixed>
     */
    private function prepareStudentRow(array $student, array $sectionById): array
    {
        $student = DataEncryptor::decryptUserRowForDisplay($student);

        $student['display_name'] = UserModel::fullName($student);

        $gradeDigit = GradeLevel::normalize($student['grade_level'] ?? '');
        $student['grade_label'] = $gradeDigit !== ''
            ? GradeLevel::label($gradeDigit)
            : '—';

        $sectionId = (int) ($student['section_id'] ?? 0);
        $rawName   = $sectionId > 0 ? trim((string) ($sectionById[$sectionId] ?? '')) : '';
        $student['section_label'] = self::formatSectionLabel($rawName, $gradeDigit);

        $birthdate = trim((string) ($student['birthdate'] ?? ''));
        if ($birthdate !== '') {
            $ts = strtotime($birthdate);
            $student['birthdate_display'] = $ts !== false ? date('M j, Y', $ts) : $birthdate;
        } else {
            $student['birthdate_display'] = '—';
        }

        $student['guardian_name_display']    = trim((string) ($student['guardian_name'] ?? '')) ?: '—';
        $student['guardian_contact_display'] = trim((string) ($student['guardian_contact'] ?? '')) ?: '—';
        $student['address_display']          = trim((string) ($student['address'] ?? '')) ?: '—';

        $typeSlug = strtolower(trim((string) ($student['student_type'] ?? '')));
        $student['student_type_label'] = StudentEnrollmentType::label($typeSlug) ?: '—';
        $student['previous_school_display'] = trim((string) ($student['previous_school'] ?? '')) ?: '—';

        return $student;
    }

    /**
     * Section name only — blank when unassigned or placeholder (e.g. "Section #1").
     */
    private static function formatSectionLabel(string $sectionName, string $gradeDigit): string
    {
        $name = trim($sectionName);
        if ($name === '') {
            return '—';
        }
        if (preg_match('/^section\s*#\d+$/i', $name)) {
            return '—';
        }
        if (preg_match('/^grade\s*([1-6])\s*[-–]\s*(.+)$/iu', $name, $m)) {
            $name = trim((string) ($m[2] ?? ''));
            if ($name === '') {
                return '—';
            }
        }

        return $name;
    }
}
