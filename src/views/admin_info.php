<?php
define('PAGE_RANGE', 2);
define('PAGE_LIMIT', 25);

$booth = Booth::find($_SESSION['auth_admin']);
$search = $_GET['s'] ?? '';

$timeslot_id = $_GET['t'] ?? null;
$page = intval($_GET['p'] ?? 1);
$offset = ($page - 1) * PAGE_LIMIT;

$reg_results = $booth->get_booth_registrations($timeslot_id, PAGE_LIMIT, $offset, $search);
$time_results = execute("SELECT timeslot_id, timestart, timeend FROM Timeslots WHERE event_id = ?", [$booth->event_id])->fetchAll();
$count_page = count($reg_results);

$count = $booth->count_booth_registrations($timeslot_id, $search);
$num_page = ceil($count / floatval(PAGE_LIMIT));
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Page</title>
  <?php include __DIR__ . '/assets.php' ?>
</head>

<body class="bg-light-subtle">
  <div class="container my-5">
    <h1 class="mb-4">
      Registrations summary
    </h1>

    <div class="card text-bg-light">
      <div class="card-header d-flex">
        <?= $booth->presentor ?><?= str_ends_with($booth->presentor, 's') ? "'" : "'s" ?> booth
        <a href="./admin.php?logout=logout" class="btn btn-sm btn-primary ms-auto">Logout</a>
      </div>
      <div class="card-body p-4">
        <div class="row">

          <em class="col-12 col-lg-auto">
            <?php if ($count == 0) {
              echo "No registration";
            } else {
              echo "Showing " . strval($offset + 1) . '-' . strval($offset + $count_page) . " of " . $count . " registrations";
            } ?>
          </em>

          <div class="col col-lg-auto ms-auto">
            <form action="admin.php" method="get" class="input-group">
              <?php if ($timeslot_id): ?>
                <input type="hidden" name="t" value="<?= $timeslot_id ?>">
              <?php endif ?>
              <input class="form-control form-control-sm" type="text" placeholder="Search" name="s" value="<?= $search ?>">
              <button class="btn btn-sm btn-success">
                Search
              </button>
            </form>
          </div>

          <div class="dropdown mb-4 col-auto ms-2">
            <button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
              Select Time Slot
            </button>
            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
              <li><a class="dropdown-item" href="./admin.php">All registrations</a></li>
              <?php foreach ($time_results as $index => $time):
                $start = new DateTime($time['timestart']);
                $end = new DateTime($time['timeend']);
                $timeSlot = htmlspecialchars($start->format('g:i a') . ' - ' . $end->format('g:i a'));
              ?>
                <li><a class="dropdown-item" href="./admin.php?s=<?= urlencode($search) ?>&t=<?= $time['timeslot_id'] ?>"><?= $timeSlot ?></a></li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table no-wrap">
            <thead class="table-primary">
              <tr>
                <th scope="col">Registration Date</th>
                <th scope="col">Timeslot</th>
                <th scope="col">Name</th>
                <th scope="col">Sex</th>
                <th scope="col">Birthday</th>
                <th scope="col">Age</th>
                <th scope="col">Affiliation</th>
                <th scope="col">Position</th>
                <th scope="col">Type</th>
                <th scope="col">Indigenous</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($reg_results)): ?>
                <tr>
                  <td colspan="10" class="text-center">No results found</td>
                </tr>
              <?php endif; ?>

              <?php foreach ($reg_results as [$reg, $t]):
                $start = (new DateTime($t['start']))->format('g:i a');
                $end = (new DateTime($t['end']))->format('g:i a');
              ?>
                <tr>
                  <td><?= htmlspecialchars($reg->registration_date->format('Y/m/d H:i')) ?></td>
                  <td><?= $start ?>-<?= $end ?></td>
                  <td><?= htmlspecialchars($reg->name) ?></td>
                  <td>
                    <?= match ($reg->sex) {
                      'M' => 'Male',
                      'F' => 'Female',
                      'OTHER' => 'Others',
                      'BLANK' => 'Prefer not to mention',
                    } ?>
                  <td><?= htmlspecialchars($reg->birthday) ?></td>
                  <td><?= htmlspecialchars($reg->get_age()) ?></td>
                  <td><?= htmlspecialchars($reg->affiliation) ?></td>
                  <td><?= htmlspecialchars($reg->position) ?></td>
                  <td><?= htmlspecialchars($reg->type) ?></td>
                  <td><?= $reg->is_indigenous ? 'Yes' : 'No' ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <?php if ($count > 0): ?>
          <div class="row mt-4">
            <nav class="mx-auto col-auto">
              <ul class="pagination pagination-sm">
                <?php if ($page > 1): ?>
                  <li class="page-item">
                    <a
                      class="page-link"
                      href="./admin.php?s=<?= urlencode($search) ?>&<?= isset($_GET['t']) ? 't=' . strval($_GET['t']) . '&' : '' ?>p=<?= $page - 1 ?>">
                      Previous
                    </a>
                  </li>
                <?php else:  ?>
                  <li class="page-item"><a class="page-link disabled" href="#">Previous</a></li>
                <?php endif; ?>


                <?php if ($page > PAGE_RANGE + 1): ?>
                  <li class="page-item">
                    <a
                      class="page-link"
                      href="./admin.php?s=<?= urlencode($search) ?>&<?= isset($_GET['t']) ? 't=' . strval($_GET['t']) . '&' : '' ?>p=1">
                      1
                    </a>
                  </li>

                  <?php if ($page > PAGE_RANGE + 2): ?>
                    <li class="page-item disabled">
                      <span class="page-link">&hellip;</span>
                    </li>
                  <?php endif ?>
                <?php endif ?>

                <?php for ($i = max(1, $page - PAGE_RANGE); $i <= min($num_page, $page + PAGE_RANGE); ++$i): ?>
                  <li class="page-item">
                    <a
                      class="page-link <?php if ($i == $page) echo 'active'; ?>"
                      href="./admin.php?s=<?= urlencode($search) ?>&<?= isset($_GET['t']) ? 't=' . strval($_GET['t']) . '&' : '' ?>p=<?= $i ?>">
                      <?= $i ?>
                    </a>
                  </li>
                <?php endfor; ?>

                <?php if ($page < $num_page - PAGE_RANGE): ?>
                  <?php if ($page < $num_page - PAGE_RANGE - 1): ?>
                    <li class="page-item disabled">
                      <span class="page-link">&hellip;</span>
                    </li>
                  <?php endif ?>
                  <li class="page-item">
                    <a class="page-link" href="./admin.php?s=<?= urlencode($search) ?>&<?= isset($_GET['t']) ? 't=' . strval($_GET['t']) . '&' : '' ?>p=<?= $num_page ?>"><?= $num_page ?></a>
                  </li>
                <?php endif ?>


                <?php if ($page < $num_page): ?>
                  <li class="page-item"><a class="page-link" href="./admin.php?s=<?= urlencode($search) ?>&<?= isset($_GET['t']) ? 't=' . strval($_GET['t']) . '&' : '' ?>p=<?= $page + 1 ?>">Next</a></li>
                <?php else:  ?>
                  <li class="page-item"><a class="page-link disabled" href="#">Next</a></li>
                <?php endif; ?>
              </ul>
            </nav>
          </div>
        <?php endif ?>

      </div>
    </div>
  </div>
</body>

</html>
