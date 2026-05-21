<?php

namespace App\Libraries;

use App\Models\AcademicYearModel;
use App\Models\SectionModel;
use App\Models\StudentEnrollmentModel;
use App\Models\UserModel;

/**
 * Academic year lifecycle: active year, enrollment history, end-of-year promotion.
 */
class AcademicYearManager
{
    public static function getActive(): ?array
    {
        return (new AcademicYearModel())->findActive();
    }

    public static function getActiveId(): int
    {
        $row = self::getActive();

        return (int) ($row['id'] ?? 0);
    }

    public static function ensureActiveYear(): int
    {
        $id = self::getActiveId();
        if ($id > 0) {
            return $id;
        }

        $year  = (int) date('Y');
        $month = (int) date('n');
        if ($month >= 6) {
            $startYear = $year;
            $endYear   = $year + 1;
        } else {
            $startYear = $year - 1;
            $endYear   = $year;
        }
        $label = $startYear . '–' . $endYear;
        $now   = date('Y-m-d H:i:s');
        $model = new AcademicYearModel();
        $model->insert([
            'label'      => $label,
            'start_date' => $startYear . '-06-01',
            'end_date'   => $endYear . '-03-31',
            'status'     => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $model->getInsertID();
    }

    /**
     * Suggested label for the school year after the given label (e.g. 2025–2026 → 2026–2027).
     */
    public static function suggestNextLabel(?string $currentLabel = null): string
    {
        if ($currentLabel !== null && preg_match('/^(\d{4})[–\-](\d{4})$/u', trim($currentLabel), $m)) {
            $start = (int) $m[1] + 1;
            $end   = (int) $m[2] + 1;

            return $start . '–' . $end;
        }

        $year  = (int) date('Y');
        $month = (int) date('n');
        if ($month >= 6) {
            return $year . '–' . ($year + 1);
        }

        return ($year - 1) . '–' . $year;
    }

    /**
     * @return array{ok: bool, message: string, id?: int}
     */
    public static function createYear(string $label, ?string $startDate, ?string $endDate, string $status = 'planning'): array
    {
        $label = trim($label);
        if ($label === '') {
            return ['ok' => false, 'message' => 'Academic year label is required (e.g. 2026–2027).'];
        }
        if (! preg_match('/^\d{4}[–\-]\d{4}$/u', $label)) {
            return ['ok' => false, 'message' => 'Use format YYYY–YYYY (e.g. 2026–2027).'];
        }

        $model = new AcademicYearModel();
        if ($model->where('label', $label)->first() !== null) {
            return ['ok' => false, 'message' => 'That academic year already exists.'];
        }

        if ($status === 'active') {
            $model->where('status', 'active')->set(['status' => 'planning', 'updated_at' => date('Y-m-d H:i:s')])->update();
        }

        $model->insert([
            'label'      => $label,
            'start_date' => $startDate !== '' ? $startDate : null,
            'end_date'   => $endDate !== '' ? $endDate : null,
            'status'     => in_array($status, ['planning', 'active', 'closed'], true) ? $status : 'planning',
        ]);

        return ['ok' => true, 'message' => 'Academic year created.', 'id' => (int) $model->getInsertID()];
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public static function setActive(int $yearId): array
    {
        $model = new AcademicYearModel();
        $year  = $model->find($yearId);
        if ($year === null) {
            return ['ok' => false, 'message' => 'Academic year not found.'];
        }
        if (($year['status'] ?? '') === 'closed') {
            return ['ok' => false, 'message' => 'Cannot activate a closed academic year. Create a new year instead.'];
        }

        $db = \Config\Database::connect();
        $db->transStart();
        $model->where('status', 'active')->set(['status' => 'planning', 'updated_at' => date('Y-m-d H:i:s')])->update();
        $model->update($yearId, ['status' => 'active']);
        $db->transComplete();

        if (! $db->transStatus()) {
            return ['ok' => false, 'message' => 'Could not set active academic year.'];
        }

        AuditLogger::log('ACADEMIC_YEAR_ACTIVATED', null, 'academic_years', $yearId, 'Activated academic year ' . ($year['label'] ?? ''));

        return ['ok' => true, 'message' => 'Academic year ' . ($year['label'] ?? '') . ' is now active.'];
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public static function setInactive(int $yearId): array
    {
        $model = new AcademicYearModel();
        $year  = $model->find($yearId);
        if ($year === null) {
            return ['ok' => false, 'message' => 'Academic year not found.'];
        }
        if (($year['status'] ?? '') === 'closed') {
            return ['ok' => false, 'message' => 'Closed academic years cannot be changed. Use Close & promote for end-of-year.'];
        }
        if (($year['status'] ?? '') !== 'active') {
            return ['ok' => false, 'message' => 'This academic year is already inactive.'];
        }

        $model->update($yearId, [
            'status'     => 'planning',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        AuditLogger::log(
            'ACADEMIC_YEAR_DEACTIVATED',
            null,
            'academic_years',
            $yearId,
            'Deactivated academic year ' . ($year['label'] ?? '')
        );

        return ['ok' => true, 'message' => 'Academic year ' . ($year['label'] ?? '') . ' is now inactive.'];
    }

    /**
     * Keep student_enrollments in sync with users table for the active year.
     */
    public static function syncStudentEnrollment(int $userId): void
    {
        $users = new UserModel();
        $user  = $users->find($userId);
        if ($user === null || ! SectionEnrollment::isStudentUser($user)) {
            return;
        }

        $ayId = self::ensureActiveYear();
        $enrollModel = new StudentEnrollmentModel();
        $existing    = $enrollModel->where('user_id', $userId)->where('academic_year_id', $ayId)->first();

        $sectionId = (int) ($user['section_id'] ?? 0);
        $sec       = null;
        $snapshot  = null;
        $secName   = null;
        if ($sectionId > 0) {
            $sec = (new SectionModel())->find($sectionId);
            if ($sec !== null) {
                $secName  = $sec['name'] ?? null;
                $sched    = $sec['class_schedule'] ?? null;
                $snapshot = is_string($sched) ? $sched : ($sched !== null ? json_encode($sched) : null);
            }
        }

        $payload = [
            'grade_level'           => $user['grade_level'] ?? null,
            'section_id'            => $sectionId > 0 ? $sectionId : null,
            'section_name_snapshot' => $secName,
            'subjects_snapshot'     => $snapshot,
            'outcome'               => 'enrolled',
            'is_current'            => 1,
            'updated_at'            => date('Y-m-d H:i:s'),
        ];

        if ($existing !== null) {
            $enrollModel->update((int) $existing['id'], $payload);
        } else {
            $enrollModel->where('user_id', $userId)->where('is_current', 1)->set(['is_current' => 0])->update();
            $enrollModel->insert(array_merge($payload, [
                'user_id'          => $userId,
                'academic_year_id' => $ayId,
                'created_at'       => date('Y-m-d H:i:s'),
            ]));
        }
    }

    /**
     * @param list<int> $retainedStudentIds
     * @return array{
     *   students: list<array<string, mixed>>,
     *   promote: int,
     *   retain: int,
     *   graduate: int,
     *   skipped: int,
     *   closing_year: array<string, mixed>|null,
     *   next_label: string
     * }
     */
    public static function previewEndOfYear(int $closingYearId, array $retainedStudentIds = []): array
    {
        $retained = array_flip(array_map('intval', $retainedStudentIds));
        $year     = (new AcademicYearModel())->find($closingYearId);
        $students = self::studentsForYear($closingYearId);

        $promote = $retain = $graduate = $skipped = 0;
        $rows    = [];

        foreach ($students as $student) {
            $id    = (int) $student['id'];
            $grade = GradeLevel::normalize($student['grade_level'] ?? '');
            $isRetained = isset($retained[$id]);
            $nextGrade  = null;

            if ($grade === '') {
                $action = 'skipped';
                $skipped++;
            } elseif ($isRetained) {
                $action = 'retain';
                $retain++;
                $nextGrade = $grade;
            } elseif (GradeLevel::isGraduating($grade)) {
                $action = 'graduate';
                $graduate++;
                $nextGrade = null;
            } else {
                $action = 'promote';
                $promote++;
                $nextGrade = GradeLevel::next($grade);
            }

            $rows[] = [
                'id'           => $id,
                'name'         => UserModel::fullName($student),
                'grade_level'  => $grade,
                'grade_label'  => GradeLevel::label($grade),
                'section_name' => $student['section_name'] ?? '—',
                'action'       => $action,
                'next_grade'   => $nextGrade,
                'next_label'   => $nextGrade !== null ? GradeLevel::label($nextGrade) : 'Graduated',
            ];
        }

        return [
            'students'      => $rows,
            'promote'       => $promote,
            'retain'        => $retain,
            'graduate'      => $graduate,
            'skipped'       => $skipped,
            'closing_year'  => $year,
            'next_label'    => self::suggestNextLabel($year['label'] ?? null),
        ];
    }

    /**
     * Close current year, promote students, activate the next year, optionally clone section shells.
     *
     * @param list<int> $retainedStudentIds
     * @return array{ok: bool, message: string, stats?: array<string, int>}
     */
    public static function executeEndOfYear(
        int $closingYearId,
        string $newYearLabel,
        array $retainedStudentIds,
        int $actorUserId,
        bool $cloneSections = true
    ): array {
        $active = (new AcademicYearModel())->find($closingYearId);
        if ($active === null || ($active['status'] ?? '') !== 'active') {
            return ['ok' => false, 'message' => 'Only the active academic year can be closed from this wizard.'];
        }

        $create = self::createYear($newYearLabel, null, null, 'planning');
        if (! $create['ok']) {
            return $create;
        }
        $newYearId = (int) ($create['id'] ?? 0);

        $retained = array_flip(array_map('intval', $retainedStudentIds));
        $students = self::studentsForYear($closingYearId);
        $users    = new UserModel();
        $enroll   = new StudentEnrollmentModel();
        $sections = new SectionModel();
        $now      = date('Y-m-d H:i:s');

        $stats = ['promoted' => 0, 'retained' => 0, 'graduated' => 0, 'skipped' => 0, 'sections_cloned' => 0, 'teachers_carried' => 0];

        $db = \Config\Database::connect();
        $db->transStart();

        foreach ($students as $student) {
            $userId = (int) $student['id'];
            $grade  = GradeLevel::normalize($student['grade_level'] ?? '');
            $sectionId = (int) ($student['section_id'] ?? 0);
            $secRow = $sectionId > 0 ? $sections->find($sectionId) : null;

            $snapshot = null;
            if ($secRow !== null && ! empty($secRow['class_schedule'])) {
                $sched = $secRow['class_schedule'];
                $snapshot = is_string($sched) ? $sched : json_encode($sched);
            }

            if ($grade === '') {
                $stats['skipped']++;
                $enroll->where('user_id', $userId)->where('is_current', 1)->set(['is_current' => 0, 'updated_at' => $now])->update();
                continue;
            }

            $isRetained = isset($retained[$userId]);
            if ($isRetained) {
                $outcome   = 'retained';
                $nextGrade = $grade;
                $stats['retained']++;
            } elseif (GradeLevel::isGraduating($grade)) {
                $outcome   = 'graduated';
                $nextGrade = null;
                $stats['graduated']++;
            } else {
                $outcome   = 'promoted';
                $nextGrade = GradeLevel::next($grade);
                $stats['promoted']++;
            }

            $existing = $enroll->where('user_id', $userId)->where('academic_year_id', $closingYearId)->first();
            $archivePayload = [
                'grade_level'           => $grade,
                'section_id'            => $sectionId > 0 ? $sectionId : null,
                'section_name_snapshot' => $secRow['name'] ?? ($student['section_name'] ?? null),
                'subjects_snapshot'     => $snapshot,
                'outcome'               => $outcome,
                'is_current'            => 0,
                'updated_at'            => $now,
            ];
            if ($existing !== null) {
                $enroll->update((int) $existing['id'], $archivePayload);
            } else {
                $enroll->insert(array_merge($archivePayload, [
                    'user_id'          => $userId,
                    'academic_year_id' => $closingYearId,
                    'created_at'       => $now,
                ]));
            }
            $enroll->where('user_id', $userId)->where('is_current', 1)->where('academic_year_id !=', $newYearId)->set(['is_current' => 0, 'updated_at' => $now])->update();

            if ($outcome === 'graduated') {
                $users->update($userId, [
                    'grade_level' => $grade,
                    'section_id'  => null,
                ]);
            } elseif ($nextGrade !== null) {
                $users->update($userId, [
                    'grade_level' => $nextGrade,
                    'section_id'  => null,
                ]);
                $enroll->insert([
                    'user_id'               => $userId,
                    'academic_year_id'      => $newYearId,
                    'grade_level'           => $nextGrade,
                    'section_id'            => null,
                    'section_name_snapshot' => null,
                    'subjects_snapshot'     => null,
                    'outcome'               => 'enrolled',
                    'is_current'            => 1,
                    'created_at'            => $now,
                    'updated_at'            => $now,
                ]);
            }
        }

        if ($cloneSections) {
            /** @var array<int, int> $sectionIdMap old section id => new section id */
            $sectionIdMap = [];
            $oldSections = $sections->where('academic_year_id', $closingYearId)->findAll();
            foreach ($oldSections as $sec) {
                $oldSectionId = (int) ($sec['id'] ?? 0);
                $g = GradeLevel::normalize($sec['grade_level'] ?? '');
                if ($g === '' || $g === (string) GradeLevel::MAX) {
                    continue;
                }
                $nextG = GradeLevel::next($g);
                if ($nextG === null) {
                    continue;
                }
                $sections->insert([
                    'name'             => $sec['name'] ?? ('Grade ' . $nextG),
                    'grade_level'      => $nextG,
                    'academic_year_id' => $newYearId,
                    'class_schedule'   => self::bumpScheduleSubjects($sec['class_schedule'] ?? null, $g, $nextG),
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ]);
                $newSectionId = (int) $sections->getInsertID();
                if ($oldSectionId > 0 && $newSectionId > 0) {
                    $sectionIdMap[$oldSectionId] = $newSectionId;
                }
                $stats['sections_cloned']++;
            }
            $stats['teachers_carried'] = self::carryTeacherAssignmentsToNewSections($sectionIdMap, $now);
        }

        (new AcademicYearModel())->update($closingYearId, [
            'status'    => 'closed',
            'closed_at' => $now,
            'closed_by' => $actorUserId > 0 ? $actorUserId : null,
        ]);

        $ayModel = new AcademicYearModel();
        $ayModel->where('status', 'active')->set(['status' => 'closed', 'updated_at' => $now])->update();
        $ayModel->update($newYearId, ['status' => 'active']);

        $db->transComplete();

        if (! $db->transStatus()) {
            return ['ok' => false, 'message' => 'End-of-year process failed. No changes were saved.'];
        }

        $label = $active['label'] ?? '';
        AuditLogger::log(
            'ACADEMIC_YEAR_CLOSED',
            $actorUserId,
            'academic_years',
            $closingYearId,
            'Closed ' . $label . ' → ' . $newYearLabel
                . '. Promoted: ' . $stats['promoted']
                . ', Retained: ' . $stats['retained']
                . ', Graduated: ' . $stats['graduated']
                . ', Teacher assignments kept: ' . ($stats['teachers_carried'] ?? 0)
        );

        return [
            'ok'      => true,
            'message' => 'School year closed successfully. ' . $newYearLabel . ' is now active.',
            'stats'   => $stats,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function studentsForYear(int $academicYearId): array
    {
        $ayId = $academicYearId > 0 ? $academicYearId : self::ensureActiveYear();
        $roles = SectionEnrollment::studentRoleSlugs();

        return (new UserModel())
            ->select('users.*, sections.name as section_name')
            ->join('sections', 'sections.id = users.section_id', 'left')
            ->whereIn('users.role', $roles)
            ->orderBy('users.grade_level', 'ASC')
            ->orderBy('users.name', 'ASC')
            ->findAll();
    }

    /**
     * Enrollment history for a student (all years).
     *
     * @return list<array<string, mixed>>
     */
    public static function enrollmentHistory(int $userId): array
    {
        return \Config\Database::connect()
            ->table('student_enrollments se')
            ->select('se.*, ay.label as academic_year_label, ay.status as academic_year_status')
            ->join('academic_years ay', 'ay.id = se.academic_year_id', 'inner')
            ->where('se.user_id', $userId)
            ->orderBy('ay.label', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function enrollmentsForYear(int $academicYearId, int $limit = 500, int $offset = 0): array
    {
        return \Config\Database::connect()
            ->table('student_enrollments se')
            ->select('se.*, u.name, u.surname, u.first_name, u.username, u.section_id, ay.label as academic_year_label, s.name as live_section_name, s.grade_level as live_section_grade')
            ->join('users u', 'u.id = se.user_id', 'inner')
            ->join('academic_years ay', 'ay.id = se.academic_year_id', 'inner')
            ->join('sections s', 's.id = u.section_id AND se.is_current = 1', 'left', false)
            ->where('se.academic_year_id', $academicYearId)
            ->orderBy('se.grade_level', 'ASC')
            ->orderBy('u.name', 'ASC')
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();
    }

    public static function enrollmentSectionLabel(array $row): string
    {
        $gradeDigit = GradeLevel::normalize($row['grade_level'] ?? '');

        if (! empty($row['is_current'])) {
            $live = trim((string) ($row['live_section_name'] ?? ''));
            $label = self::cleanSectionName($live, $gradeDigit);
            if ($label !== '—') {
                return $label;
            }

            return 'Not assigned yet';
        }

        $snap = trim((string) ($row['section_name_snapshot'] ?? ''));
        $label = self::cleanSectionName($snap, $gradeDigit);

        return $label !== '—' ? $label : '—';
    }

    private static function cleanSectionName(string $name, string $gradeDigit = ''): string
    {
        $name = trim($name);
        if ($name === '' || preg_match('/^section\s*#\d+$/i', $name)) {
            return '—';
        }
        if (preg_match('/^grade\s*([1-6])\s*[-–]\s*(.+)$/iu', $name, $m)) {
            $name = trim((string) ($m[2] ?? ''));
        }

        return $name !== '' ? $name : '—';
    }

    public static function countEnrollmentsForYear(int $academicYearId): int
    {
        return (int) \Config\Database::connect()
            ->table('student_enrollments')
            ->where('academic_year_id', $academicYearId)
            ->countAllResults();
    }

    /**
     * @param mixed $schedule
     */
    private static function bumpScheduleSubjects($schedule, string $fromGrade, string $toGrade): ?string
    {
        if ($schedule === null || $schedule === '') {
            return null;
        }
        $json = is_string($schedule) ? $schedule : json_encode($schedule);
        if ($json === false || $json === '') {
            return null;
        }

        $subjects = self::depedSubjectsForGrade($toGrade);
        $decoded  = json_decode($json, true);
        if (! is_array($decoded) || ! isset($decoded['slots']) || ! is_array($decoded['slots'])) {
            return $json;
        }
        foreach ($decoded['slots'] as $i => $slot) {
            if (! is_array($slot)) {
                continue;
            }
            $subj = trim((string) ($slot['subject'] ?? ''));
            if ($subj !== '' && $subjects !== []) {
                $decoded['slots'][$i]['subject'] = $subjects[min($i, count($subjects) - 1)] ?? $subj;
            }
        }

        return json_encode($decoded);
    }

    /**
     * Copy adviser/subject teacher rows to cloned sections. Old assignments are kept for records.
     *
     * @param array<int, int> $sectionIdMap
     */
    private static function carryTeacherAssignmentsToNewSections(array $sectionIdMap, string $now): int
    {
        if ($sectionIdMap === []) {
            return 0;
        }

        $db    = \Config\Database::connect();
        $count = 0;

        foreach ($sectionIdMap as $oldSectionId => $newSectionId) {
            $rows = $db->table('teacher_sections')
                ->where('section_id', (int) $oldSectionId)
                ->groupStart()
                    ->where('status', 'accepted')
                    ->orWhere('status', 'pending')
                ->groupEnd()
                ->get()
                ->getResultArray();

            foreach ($rows as $row) {
                $teacherId = (int) ($row['teacher_id'] ?? 0);
                if ($teacherId <= 0) {
                    continue;
                }
                $role    = (string) ($row['assignment_role'] ?? 'ADVISER');
                $subject = (string) ($row['subject_name'] ?? '');

                $exists = $db->table('teacher_sections')
                    ->where('teacher_id', $teacherId)
                    ->where('section_id', (int) $newSectionId)
                    ->where('assignment_role', $role)
                    ->where('subject_name', $subject)
                    ->groupStart()
                        ->where('status', 'accepted')
                        ->orWhere('status', 'pending')
                    ->groupEnd()
                    ->countAllResults();

                if ($exists > 0) {
                    continue;
                }

                $db->table('teacher_sections')->insert([
                    'teacher_id'       => $teacherId,
                    'section_id'       => (int) $newSectionId,
                    'assignment_role'  => $role,
                    'subject_name'     => $subject !== '' ? $subject : null,
                    'status'           => (string) ($row['status'] ?? 'accepted'),
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ]);
                $count++;
            }
        }

        return $count;
    }

    /**
     * @return list<string>
     */
    private static function depedSubjectsForGrade(string $grade): array
    {
        $map = [
            '1' => ['English', 'Filipino', 'Mathematics', 'Science', 'Araling Panlipunan', 'MTB-MLE', 'MAPEH', 'ESP'],
            '2' => ['English', 'Filipino', 'Mathematics', 'Science', 'Araling Panlipunan', 'MTB-MLE', 'MAPEH', 'ESP'],
            '3' => ['English', 'Filipino', 'Mathematics', 'Science', 'Araling Panlipunan', 'MTB-MLE', 'MAPEH', 'ESP'],
            '4' => ['English', 'Filipino', 'Mathematics', 'Science', 'Araling Panlipunan', 'MAPEH', 'ESP', 'EPP / Homeroom'],
            '5' => ['English', 'Filipino', 'Mathematics', 'Science', 'Araling Panlipunan', 'MAPEH', 'ESP', 'EPP / Homeroom'],
            '6' => ['English', 'Filipino', 'Mathematics', 'Science', 'Araling Panlipunan', 'MAPEH', 'ESP', 'EPP / Homeroom'],
        ];
        $g = GradeLevel::normalize($grade);

        return $map[$g] ?? [];
    }
}
