<?php

namespace App\Libraries;

use App\Models\UserModel;

/**
 * Live AJES database context for AjesAI — announcements, sections, enrollments, schedules.
 * Student viewers only receive data for their enrolled grade/section.
 */
class AjesKnowledge
{
    private const MAX_STUDENTS_PER_SECTION = 35;

    private const MAX_SECTIONS = 24;

    /**
     * Text block appended to the AI system prompt (refreshed on each chat message).
     *
     * @param array{sender_role?: string, role?: string, section_id?: int, grade_level?: string, section_name?: string} $viewer
     */
    public static function contextBlock(array $viewer = []): string
    {
        $scope = self::resolveScope($viewer);

        $lines = [
            '=== AJES CRIER SYSTEM KNOWLEDGE (official data from the school database) ===',
            'School: Ano Jay Elementary School (AJES).',
            'Platform: AJES Crier — announcements, chat, sections, student enrollment, guidance records, teacher assignments.',
            '',
            'IMPORTANT: You HAVE the data below. Use it to answer. Do NOT say you lack access to enrollment, subjects, or schedules when the answer is listed here.',
            'Do NOT share guardian phone numbers, passwords, or full home addresses. Student names and grade/section are OK.',
        ];

        if ($scope['mode'] === 'student') {
            $lines[] = '';
            $lines[] = '--- STUDENT ACCESS SCOPE (privacy — enforced) ---';
            if ($scope['section_id'] > 0) {
                $lines[] = 'The person chatting is a STUDENT enrolled ONLY in: ' . $scope['section_label'] . '.';
                $lines[] = 'You may ONLY list classmates and section enrollment for THAT grade and section.';
                $lines[] = 'If they ask about another grade (e.g. Grade 1) or another section, politely refuse and say they can only view their own class (' . $scope['section_label'] . ').';
            } else {
                $lines[] = 'The person chatting is a STUDENT with no section assigned yet.';
                $lines[] = 'Do NOT list enrolled students or class rosters. Only general school info and school-wide announcements.';
            }
            $lines[] = '--- END STUDENT ACCESS SCOPE ---';
        }

        foreach (self::schoolHoursLines() as $line) {
            $lines[] = $line;
        }

        $lines[] = '';

        foreach (self::recentAnnouncements($scope) as $row) {
            $title = trim((string) ($row['title'] ?? ''));
            $body  = trim(strip_tags((string) ($row['body'] ?? '')));
            if ($title === '') {
                continue;
            }
            $body = $body !== '' ? character_limiter($body, 200) : '';
            $lines[] = 'Announcement: ' . $title . ($body !== '' ? ' — ' . $body : '');
        }

        $lines[] = '';

        if ($scope['mode'] === 'student' && $scope['section_id'] > 0) {
            $lines[] = '--- YOUR SECTION & CLASSMATES (only section you may disclose to this student) ---';
        } else {
            $lines[] = '--- SECTIONS & ENROLLED STUDENTS (from users table) ---';
        }

        foreach (self::sectionsWithStudents($scope) as $block) {
            $lines[] = $block;
        }

        $lines[] = '--- END AJES KNOWLEDGE ---';

        $text = implode("\n", $lines);
        if (strlen($text) > 12000) {
            $text = substr($text, 0, 11900) . "\n...(truncated for length)";
        }

        return $text;
    }

    /**
     * @param array{sender_role?: string, role?: string, section_id?: int, grade_level?: string, section_name?: string} $viewer
     *
     * @return array{mode: string, section_id?: int, grade_level?: string, section_name?: string, section_label?: string}
     */
    private static function resolveScope(array $viewer): array
    {
        $role = strtoupper(trim((string) ($viewer['role'] ?? $viewer['sender_role'] ?? '')));

        if (! in_array($role, SectionEnrollment::studentRoleSlugs(), true)) {
            return ['mode' => 'full'];
        }

        $sectionId   = (int) ($viewer['section_id'] ?? 0);
        $grade       = trim((string) ($viewer['grade_level'] ?? ''));
        $sectionName = trim((string) ($viewer['section_name'] ?? ''));

        if ($sectionId > 0 && ($sectionName === '' || $grade === '')) {
            try {
                $db  = \Config\Database::connect();
                $sec = $db->table('sections')
                    ->select('name, grade_level')
                    ->where('id', $sectionId)
                    ->get()
                    ->getRowArray();
                if ($sec) {
                    if ($sectionName === '') {
                        $sectionName = trim((string) ($sec['name'] ?? ''));
                    }
                    if ($grade === '') {
                        $grade = trim((string) ($sec['grade_level'] ?? ''));
                    }
                }
            } catch (\Throwable $e) {
                log_message('warning', 'AjesKnowledge: resolveScope — ' . $e->getMessage());
            }
        }

        $label = '';
        if ($sectionId > 0) {
            $label = 'Grade ' . ($grade !== '' ? $grade : '?') . ' — ' . ($sectionName !== '' ? $sectionName : 'Section #' . $sectionId);
        }

        return [
            'mode'          => 'student',
            'section_id'    => $sectionId,
            'grade_level'   => $grade,
            'section_name'  => $sectionName,
            'section_label' => $label,
        ];
    }

    /**
     * @return list<string>
     */
    private static function schoolHoursLines(): array
    {
        $start = trim((string) env('SCHOOL_START_TIME', ''));
        $end   = trim((string) env('SCHOOL_DISMISSAL_TIME', ''));

        $template = self::defaultScheduleTemplate();
        if ($start === '') {
            $start = (string) ($template['slots'][0]['start'] ?? '07:30');
        }
        if ($end === '') {
            $end = (string) ($template['dismissal_time'] ?? '15:30');
        }

        return [
            'School day (AJES standard class schedule):',
            '- Classes start: ' . self::formatTime12h($start) . ' (' . $start . ')',
            '- Dismissal: ' . self::formatTime12h($end) . ' (' . $end . ')',
            '- Recess: 09:10–09:30',
            '- Lunch: 12:00–13:00',
            '- Eight 50-minute subject periods per day (see each section schedule in admin if customized).',
        ];
    }

    /**
     * @param array{mode?: string, section_id?: int} $scope
     *
     * @return list<array<string, mixed>>
     */
    private static function recentAnnouncements(array $scope): array
    {
        try {
            $db = \Config\Database::connect();

            $builder = $db->table('announcements')
                ->select('title, body, created_at')
                ->where('status', 'ACTIVE')
                ->where('deleted_at', null);

            if (($scope['mode'] ?? 'full') === 'student') {
                $sectionId = (int) ($scope['section_id'] ?? 0);
                if ($sectionId > 0) {
                    $builder->groupStart()
                        ->whereIn('audience_type', ['ALL', 'school-wide'])
                        ->orWhere('section_id', $sectionId)
                        ->groupEnd();
                } else {
                    $builder->whereIn('audience_type', ['ALL', 'school-wide']);
                }
            }

            return $builder
                ->orderBy('created_at', 'DESC')
                ->limit(8)
                ->get()
                ->getResultArray();
        } catch (\Throwable $e) {
            log_message('warning', 'AjesKnowledge: announcements — ' . $e->getMessage());

            return [];
        }
    }

    /**
     * @param array{mode?: string, section_id?: int} $scope
     *
     * @return list<string> One line block per section
     */
    private static function sectionsWithStudents(array $scope): array
    {
        try {
            $db = \Config\Database::connect();

            $isStudent = ($scope['mode'] ?? 'full') === 'student';
            $studentSectionId = (int) ($scope['section_id'] ?? 0);

            if ($isStudent && $studentSectionId < 1) {
                return ['(No section assigned to this student — classmate lists are not available.)'];
            }

            $sectionQuery = $db->table('sections')
                ->select('id, name, grade_level, class_schedule')
                ->orderBy('grade_level')
                ->orderBy('name');

            if ($isStudent) {
                $sectionQuery->where('id', $studentSectionId);
            } else {
                $sectionQuery->limit(self::MAX_SECTIONS);
            }

            $sections = $sectionQuery->get()->getResultArray();

            if ($sections === []) {
                return $isStudent
                    ? ['(Your assigned section was not found in the database.)']
                    : ['(No sections in database yet.)'];
            }

            $studentRoles = SectionEnrollment::studentRoleSlugs();
            $blocks       = [];

            foreach ($sections as $section) {
                $sectionId = (int) ($section['id'] ?? 0);
                $grade     = trim((string) ($section['grade_level'] ?? ''));
                $name      = trim((string) ($section['name'] ?? ''));
                $label     = 'Grade ' . ($grade !== '' ? $grade : '?') . ' — ' . ($name !== '' ? $name : 'Section #' . $sectionId);

                $adviser = self::sectionAdviserName($db, $sectionId);
                if ($adviser !== '') {
                    $label .= ' (Class adviser: ' . $adviser . ')';
                }

                $scheduleNote = self::scheduleStartNote($section['class_schedule'] ?? null);
                $teachersMap  = self::sectionTeachersMap($db, $sectionId);

                foreach (self::classScheduleSubjectLines($section['class_schedule'] ?? null, $adviser, $teachersMap) as $schedLine) {
                    $blocks[] = $schedLine;
                }

                $students = $db->table('users')
                    ->select('id, name, surname, first_name, middle_initial, name_suffix, grade_level, is_active')
                    ->where('section_id', $sectionId)
                    ->whereIn('role', $studentRoles)
                    ->where('deleted_at', null)
                    ->where('is_active', 1)
                    ->orderBy('surname')
                    ->orderBy('first_name')
                    ->limit(self::MAX_STUDENTS_PER_SECTION + 1)
                    ->get()
                    ->getResultArray();

                $count = count($students);
                $blocks[] = $label . ' — ' . $count . ' enrolled student(s)' . ($scheduleNote !== '' ? '; ' . $scheduleNote : '') . ':';

                if ($count === 0) {
                    $blocks[] = '  (none enrolled yet)';
                    continue;
                }

                $shown = 0;
                foreach ($students as $student) {
                    if ($shown >= self::MAX_STUDENTS_PER_SECTION) {
                        $blocks[] = '  ... and ' . ($count - self::MAX_STUDENTS_PER_SECTION) . ' more student(s)';
                        break;
                    }
                    $blocks[] = '  - ' . UserModel::fullName($student);
                    $shown++;
                }
            }

            return $blocks;
        } catch (\Throwable $e) {
            log_message('warning', 'AjesKnowledge: sections — ' . $e->getMessage());

            return ['(Could not load section enrollment data.)'];
        }
    }

    private static function sectionAdviserName($db, int $sectionId): string
    {
        if ($sectionId < 1) {
            return '';
        }

        try {
            $row = $db->table('teacher_sections')
                ->select('users.name, users.surname, users.first_name, users.username')
                ->join('users', 'users.id = teacher_sections.teacher_id')
                ->where('teacher_sections.section_id', $sectionId)
                ->where('teacher_sections.assignment_role', 'ADVISER')
                ->where('teacher_sections.status', 'accepted')
                ->get()
                ->getRowArray();

            if (! $row) {
                return '';
            }

            return UserModel::fullName($row);
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * @return array{adviser: string, by_subject: array<string, string>}
     */
    private static function sectionTeachersMap($db, int $sectionId): array
    {
        $result = ['adviser' => '', 'by_subject' => []];
        if ($sectionId < 1) {
            return $result;
        }

        try {
            $rows = $db->table('teacher_sections')
                ->select('teacher_sections.subject_name, teacher_sections.assignment_role, users.name, users.surname, users.first_name, users.username')
                ->join('users', 'users.id = teacher_sections.teacher_id')
                ->where('teacher_sections.section_id', $sectionId)
                ->where('teacher_sections.status', 'accepted')
                ->get()
                ->getResultArray();

            foreach ($rows as $row) {
                $teacherName = UserModel::fullName($row);
                $role        = strtoupper(trim((string) ($row['assignment_role'] ?? '')));
                if ($role === 'ADVISER') {
                    if ($result['adviser'] === '') {
                        $result['adviser'] = $teacherName;
                    }
                    continue;
                }

                $subj = trim((string) ($row['subject_name'] ?? ''));
                if ($subj !== '') {
                    $result['by_subject'][strtolower($subj)] = $teacherName;
                }
            }
        } catch (\Throwable $e) {
            log_message('warning', 'AjesKnowledge: sectionTeachersMap — ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * @param array{adviser: string, by_subject: array<string, string>} $teachersMap
     *
     * @return list<string>
     */
    private static function classScheduleSubjectLines(?string $raw, string $adviserFromSection, array $teachersMap): array
    {
        $decoded = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;
        if (! is_array($decoded) || ! isset($decoded['slots']) || ! is_array($decoded['slots'])) {
            return ['  (No subject schedule saved for this section yet.)'];
        }

        $adviser = $adviserFromSection !== '' ? $adviserFromSection : ($teachersMap['adviser'] ?? '');
        $bySubject = $teachersMap['by_subject'] ?? [];

        $subjectNames = [];
        foreach ($decoded['slots'] as $slot) {
            if (! is_array($slot)) {
                continue;
            }
            $subj = trim((string) ($slot['subject'] ?? ''));
            if ($subj !== '') {
                $subjectNames[] = $subj;
            }
        }

        if ($subjectNames === []) {
            return ['  (Subject names not set in section schedule yet — ask the class adviser or admin.)'];
        }

        $uniqueSubjects = array_values(array_unique($subjectNames));
        $lines          = ['  Subjects in this section: ' . implode(', ', $uniqueSubjects), '  Daily class schedule:'];

        foreach ($decoded['slots'] as $slot) {
            if (! is_array($slot)) {
                continue;
            }

            $subj = trim((string) ($slot['subject'] ?? ''));
            if ($subj === '') {
                continue;
            }

            $slotNo = (int) ($slot['slot'] ?? 0);
            $start  = trim((string) ($slot['start'] ?? ''));
            $end    = trim((string) ($slot['end'] ?? ''));
            $time   = '';
            if ($start !== '' && $end !== '') {
                $time = self::formatTime12h($start) . '–' . self::formatTime12h($end);
            } elseif ($start !== '') {
                $time = 'starts ' . self::formatTime12h($start);
            }

            $teacher = $bySubject[strtolower($subj)] ?? '';
            if ($teacher === '' && ! empty($slot['adviser_teaches']) && $adviser !== '') {
                $teacher = $adviser . ' (class adviser)';
            } elseif ($teacher !== '') {
                $teacher .= ' (subject teacher)';
            }

            $period = $slotNo > 0 ? 'Period ' . $slotNo : 'Period';
            $line   = '    - ' . $period;
            if ($time !== '') {
                $line .= ' (' . $time . ')';
            }
            $line .= ': ' . $subj;
            if ($teacher !== '') {
                $line .= ' — ' . $teacher;
            }
            $lines[] = $line;
        }

        if (! empty($decoded['breaks']) && is_array($decoded['breaks'])) {
            foreach ($decoded['breaks'] as $break) {
                if (! is_array($break)) {
                    continue;
                }
                $label = trim((string) ($break['label'] ?? 'Break'));
                $bStart = trim((string) ($break['start'] ?? ''));
                $bEnd   = trim((string) ($break['end'] ?? ''));
                if ($bStart !== '' && $bEnd !== '') {
                    $lines[] = '    - ' . $label . ': ' . self::formatTime12h($bStart) . '–' . self::formatTime12h($bEnd);
                }
            }
        }

        return $lines;
    }

    private static function scheduleStartNote(?string $raw): string
    {
        $decoded = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;
        if (! is_array($decoded)) {
            return '';
        }

        $start = trim((string) ($decoded['slots'][0]['start'] ?? ''));
        $end   = trim((string) ($decoded['dismissal_time'] ?? ''));

        if ($start === '' && $end === '') {
            return '';
        }

        $parts = [];
        if ($start !== '') {
            $parts[] = 'section class start ' . self::formatTime12h($start);
        }
        if ($end !== '') {
            $parts[] = 'dismissal ' . self::formatTime12h($end);
        }

        return implode(', ', $parts);
    }

    /**
     * @return array{slots: list<array<string, mixed>>, breaks: list<array<string, mixed>>, dismissal_time: string}
     */
    private static function defaultScheduleTemplate(): array
    {
        return [
            'slots' => [
                ['slot' => 1, 'start' => '07:30', 'end' => '08:20'],
            ],
            'breaks' => [],
            'dismissal_time' => '15:30',
        ];
    }

    private static function formatTime12h(string $time24): string
    {
        $time24 = trim($time24);
        if ($time24 === '') {
            return '';
        }
        $ts = strtotime('today ' . $time24);

        return $ts !== false ? date('g:i A', $ts) : $time24;
    }
}
