<?php

namespace App\Controllers;

class Dashboard extends BaseController
{
    public function index(): string
    {
        $role = session()->get('role') ?? 'GUEST';

        return match ($role) {
            'ADMIN'      => view('Admin/dashboard'),
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
        return view('Admin/dashboard');
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

