<?php

namespace App\Controllers;

use App\Models\AnnouncementModel;
use App\Models\UserModel;
use App\Models\SectionModel;
use App\Models\MessageModel;

class Dashboard extends BaseController
{
    public function index(): string
    {
        $role = session()->get('role') ?? 'GUEST';

        return match ($role) {
            'ADMIN'      => $this->admin(),
            'PRINCIPAL'  => view('Principal/dashboard'),
            'ANNOUNCER'  => view('Announcer/dashboard'),
            'TEACHER'    => view('Teacher/dashboard'),
            'GUIDANCE'   => view('Guidance/dashboard'),
            'STUDENT'    => view('Student/dashboard'),
            default      => view('Auth/login'),
        };
    }

    public function admin(): string
    {
        $users        = new UserModel();
        $announcements = new AnnouncementModel();

        $totalUsers = $users->countAllResults();
        $todayStart = date('Y-m-d 00:00:00');
        $announcementsToday = $announcements->where('created_at >=', $todayStart)->countAllResults();

        $recentAnnouncements = $announcements->orderBy('created_at', 'DESC')->findAll(10);
        $authorIds = array_filter(array_unique(array_column($recentAnnouncements, 'created_by')));
        $authors = [];
        if ($authorIds !== []) {
            $authorList = $users->whereIn('id', $authorIds)->findAll();
            foreach ($authorList as $u) {
                $authors[(int) $u['id']] = $u['name'] ?? $u['username'] ?? 'User #' . $u['id'];
            }
        }

        $activityDays = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-{$i} days"));
            $dayStart = $day . ' 00:00:00';
            $dayEnd   = $day . ' 23:59:59';
            $count = $announcements->where('created_at >=', $dayStart)->where('created_at <=', $dayEnd)->countAllResults();
            $activityDays[] = ['date' => $day, 'label' => date('M j', strtotime($day)), 'count' => $count];
        }

        $data = [
            'role'                => 'ADMIN',
            'name'                => session()->get('name') ?? 'Administrator',
            'total_users'         => $totalUsers,
            'announcements_today' => $announcementsToday,
            'active_modules'      => 4,
            'recent_announcements'=> $recentAnnouncements,
            'authors'             => $authors,
            'activity_chart'     => $activityDays,
        ];
        return view('Admin/dashboard', $data);
    }

    public function principal(): string
    {
        return view('Principal/dashboard');
    }

    public function announcer(): string
    {
        return view('Announcer/dashboard');
    }

    public function teacher(): string
    {
        return view('Teacher/dashboard');
    }

    public function guidance(): string
    {
        return view('Guidance/dashboard');
    }

    public function student(): string
    {
        $userId = (int) (session()->get('user_id') ?? 0);
        $users = new UserModel();
        $sections = new SectionModel();
        $announcements = new AnnouncementModel();
        $messages = new MessageModel();

        $student = $users->find($userId);
        $sectionId = (int) ($student['section_id'] ?? 0);
        $section = $sectionId > 0 ? $sections->find($sectionId) : null;

        $todayStart = date('Y-m-d 00:00:00');
        $weekStart = date('Y-m-d 00:00:00', strtotime('-7 days'));

        $annBuilder = $announcements->where('status', 'ACTIVE');
        if ($sectionId > 0) {
            $annBuilder = $annBuilder->groupStart()
                ->whereIn('audience_type', ['ALL', 'school-wide'])
                ->orWhere('section_id', $sectionId)
                ->groupEnd();
        } else {
            $annBuilder = $annBuilder->whereIn('audience_type', ['ALL', 'school-wide']);
        }

        $announcementCountWeek = $annBuilder->where('created_at >=', $weekStart)->countAllResults();

        $todayBuilder = $announcements->where('status', 'ACTIVE');
        if ($sectionId > 0) {
            $todayBuilder = $todayBuilder->groupStart()
                ->whereIn('audience_type', ['ALL', 'school-wide'])
                ->orWhere('section_id', $sectionId)
                ->groupEnd();
        } else {
            $todayBuilder = $todayBuilder->whereIn('audience_type', ['ALL', 'school-wide']);
        }
        $announcementCountToday = $todayBuilder->where('created_at >=', $todayStart)->countAllResults();

        $recentBuilder = $announcements->where('status', 'ACTIVE');
        if ($sectionId > 0) {
            $recentBuilder = $recentBuilder->groupStart()
                ->whereIn('audience_type', ['ALL', 'school-wide'])
                ->orWhere('section_id', $sectionId)
                ->groupEnd();
        } else {
            $recentBuilder = $recentBuilder->whereIn('audience_type', ['ALL', 'school-wide']);
        }
        $recentAnnouncements = $recentBuilder->orderBy('created_at', 'DESC')->findAll(5);

        $unreadMessages = $messages
            ->where('receiver_id', $userId)
            ->where('status !=', 'READ')
            ->countAllResults();

        $data = [
            'role' => 'STUDENT',
            'name' => session()->get('name') ?? 'Student',
            'student_section' => $section,
            'announcement_count_week' => $announcementCountWeek,
            'announcement_count_today' => $announcementCountToday,
            'unread_messages' => $unreadMessages,
            'recent_announcements' => $recentAnnouncements,
        ];

        return view('Student/dashboard', $data);
    }
}

