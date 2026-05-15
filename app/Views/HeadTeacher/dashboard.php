<?php
use App\Libraries\RoleRegistry;

$role = strtoupper((string) (session()->get('role') ?? 'HEAD_TEACHER'));
$name = session()->get('name') ?? 'Head Teacher';
$roleLabel = RoleRegistry::displayName($role);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AJES Head Teacher Dashboard</title>
    <?php include(APPPATH . 'Views/template.php'); ?>
</head>
<body>
    <?php include(APPPATH . 'Views/template/index.php'); ?>

    <h1 class="dashboard-header">Head Teacher Dashboard</h1>

    <?php
    $role_label = $roleLabel;
    $subtitle = 'Lead instructional staff, review announcements, and monitor school communication within your assigned privileges.';
    echo view('partials/leadership_quick_access', compact('role', 'name', 'role_label', 'subtitle'));
    ?>

    </main>
</div>
</body>
</html>
