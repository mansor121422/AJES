<?php

namespace App\Controllers;

use App\Libraries\DataEncryptor;
use App\Libraries\SectionEnrollment;
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
            'role'         => session()->get('role') ?? 'ADMIN',
            'name'         => session()->get('name') ?? 'User',
        ]);
    }

    /** @return array<int, string> */
    private function sectionLabelsById(): array
    {
        $out = [];
        foreach ($this->sections->orderBy('grade_level')->orderBy('name')->findAll() as $section) {
            $id   = (int) ($section['id'] ?? 0);
            $name = trim((string) ($section['name'] ?? ''));
            $gl   = trim((string) ($section['grade_level'] ?? ''));
            if ($name === '') {
                $out[$id] = 'Section #' . $id;
                continue;
            }
            if (stripos($name, 'grade') !== false) {
                $out[$id] = $name;
            } elseif ($gl !== '') {
                $out[$id] = 'Grade ' . $gl . ' - ' . $name;
            } else {
                $out[$id] = $name;
            }
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
        $sectionId               = (int) ($student['section_id'] ?? 0);
        $student['section_label'] = $sectionId > 0
            ? ($sectionById[$sectionId] ?? 'Section #' . $sectionId)
            : '—';

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

        return $student;
    }
}
