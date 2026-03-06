<?php

namespace App\Controllers;

use App\Models\AnnouncementModel;
use App\Models\UserModel;

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
        return view('Student/dashboard');
    }
}

