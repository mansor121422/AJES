<?php
use App\Libraries\RoleRegistry;

$role = strtoupper((string) (session()->get('role') ?? 'VICE_PRINCIPAL'));
$name = session()->get('name') ?? 'Vice Principal';
$roleLabel = RoleRegistry::displayName($role);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AJES Vice Principal Dashboard</title>
    <?php include(APPPATH . 'Views/template.php'); ?>
</head>
<body>
    <?php include(APPPATH . 'Views/template/index.php'); ?>

    <h1 class="dashboard-header">Vice Principal Dashboard</h1>

    <?php
    $role_label = $roleLabel;
    echo view('partials/leadership_quick_access', compact('role', 'name', 'role_label'));
    ?>

    </main>
</div>
</body>
</html>
