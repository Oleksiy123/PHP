<?php
// Функція для перевірки, чи знищено конкретний корабель
function isShipDestroyed($grid, $shipCells) {
    foreach ($shipCells as $cell) {
        list($r, $c) = $cell;
        if ($grid[$r][$c] !== 2) { // Якщо хоча б одна частина корабля не вражена
            return false;
        }
    }
    return true;
}

// Функція для оновлення інформації про промахи
function markSurroundingAsMiss(&$grid, $shipCells, $gridSize) {
    foreach ($shipCells as $cell) {
        list($row, $col) = $cell;
        for ($i = -1; $i <= 1; $i++) {
            for ($j = -1; $j <= 1; $j++) {
                $r = $row + $i;
                $c = $col + $j;
                if ($r >= 0 && $r < $gridSize && $c >= 0 && $c < $gridSize && $grid[$r][$c] === 0) {
                    $grid[$r][$c] = 3; // Позначити клітинку як промах
                }
            }
        }
    }
}

// Функція для генерації кораблів
function generateShips($gridSize) {
    $grid = array_fill(0, $gridSize, array_fill(0, $gridSize, 0)); // Ініціалізація порожнього поля
    $ships = [
        ['length' => 4, 'count' => 1],
        ['length' => 3, 'count' => 2],
        ['length' => 2, 'count' => 3], 
        ['length' => 1, 'count' => 4],
    ];

    // Функція для перевірки можливості розміщення корабля
    function canPlaceShip($grid, $row, $col, $length, $direction, $gridSize) {
        if ($direction == 0) { // Горизонтально
            if ($col + $length > $gridSize) return false;
            for ($i = 0; $i < $length; $i++) {
                if ($grid[$row][$col + $i] != 0) return false; // Перевірка на перехрестя з іншими кораблями
            }
            // Перевірка на сусідні клітинки
            for ($i = -1; $i <= 1; $i++) {
                for ($j = -1; $j <= $length; $j++) {
                    $r = $row + $i;
                    $c = $col + $j;
                    if ($r >= 0 && $r < $gridSize && $c >= 0 && $c < $gridSize) {
                        if ($grid[$r][$c] == 1) return false; // Перевірка на сусідні кораблі
                    }
                }
            }
        } else { // Вертикально
            if ($row + $length > $gridSize) return false;
            for ($i = 0; $i < $length; $i++) {
                if ($grid[$row + $i][$col] != 0) return false; // Перевірка на перехрестя з іншими кораблями
            }
            // Перевірка на сусідні клітинки
            for ($i = -1; $i <= $length; $i++) {
                for ($j = -1; $j <= 1; $j++) {
                    $r = $row + $i;
                    $c = $col + $j;
                    if ($r >= 0 && $r < $gridSize && $c >= 0 && $c < $gridSize) {
                        if ($grid[$r][$c] == 1) return false; // Перевірка на сусідні кораблі
                    }
                }
            }
        }
        return true;
    }

    // Генерація кораблів для кожного типу
    $shipLocations = [];
    foreach ($ships as $ship) {
        $length = $ship['length'];
        $count = $ship['count'];
        for ($i = 0; $i < $count; $i++) {
            $placed = false;
            while (!$placed) {
                $direction = rand(0, 1); // 0 - горизонтальний, 1 - вертикальний
                $row = rand(0, $gridSize - 1);
                $col = rand(0, $gridSize - 1);
                if (canPlaceShip($grid, $row, $col, $length, $direction, $gridSize)) {
                    $shipCells = [];
                    if ($direction == 0) { // Горизонтально
                        for ($j = 0; $j < $length; $j++) {
                            $grid[$row][$col + $j] = 1;
                            $shipCells[] = [$row, $col + $j];
                        }
                    } else { // Вертикально
                        for ($j = 0; $j < $length; $j++) {
                            $grid[$row + $j][$col] = 1;
                            $shipCells[] = [$row + $j, $col];
                        }
                    }
                    $shipLocations[] = $shipCells;
                    $placed = true;
                }
            }
        }
    }

    return ['grid' => $grid, 'shipLocations' => $shipLocations];
}

// Ініціалізація гри
session_start();

if (isset($_POST['reset'])) {
    session_destroy();
    session_start();
}

$gridSize = 10;
if (!isset($_SESSION['enemyGrid'])) {
    $enemyData = generateShips($gridSize);
    $_SESSION['enemyGrid'] = $enemyData['grid'];
    $_SESSION['shipLocations'] = $enemyData['shipLocations'];
    $_SESSION['score'] = 0;
    $_SESSION['shipsDestroyed'] = 0;
}

$enemyGrid = $_SESSION['enemyGrid'];
$shipLocations = $_SESSION['shipLocations'];
$score = &$_SESSION['score'];
$shipsDestroyed = &$_SESSION['shipsDestroyed'];
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['reset'])) {
    $row = intval($_POST['row']);
    $col = intval($_POST['col']);

    if ($enemyGrid[$row][$col] === 1) {
        $enemyGrid[$row][$col] = 2; // Попадання
        $score += 10;
        $message = "Влучно!";

        // Перевірка, чи знищено корабель
        foreach ($shipLocations as $index => $shipCells) {
            if (isShipDestroyed($enemyGrid, $shipCells)) {
                unset($shipLocations[$index]);
                $shipsDestroyed++;
                $message = "Корабель знищено!";
                // Позначення всіх клітинок навколо корабля як промахи
                markSurroundingAsMiss($enemyGrid, $shipCells, $gridSize);
                break;
            }
        }
    } elseif ($enemyGrid[$row][$col] === 0) {
        $enemyGrid[$row][$col] = 3; // Промах
        $message = "Промах!";
    } else {
        $message = "В цю клітинку вже стріляли.";
    }

    $_SESSION['enemyGrid'] = $enemyGrid;
    $_SESSION['shipLocations'] = $shipLocations;
}
// HTML-інтерфейс
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Морський бій</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            background-color: #e3f2fd;
        }
        h1 {
            color: #0d47a1;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(11, 30px); /* +1 для нумерації */
            gap: 5px;
            justify-content: center;
            margin: 20px auto;
        }
        .cell {
            width: 30px;
            height: 30px;
            background-color: #bbdefb;
            border: 1px solid #0d47a1;
            text-align: center;
            line-height: 30px;
            font-size: 14px;
            cursor: pointer;
        }
        .cell.hit {
            background-color: red;
        }
        .cell.miss {
            background-color: gray;
        }
        .row-label, .col-label {
            font-weight: bold;
            background-color: #90caf9;
        }
        form {
            margin-top: 20px;
        }
        button {
            padding: 10px 20px;
            background-color: #0d47a1;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #1565c0;
        }
    </style>
</head>
<body>
    <h1>Морський бій</h1>
    <p>Обирайте координати та стріляйте!</p>
    <p>Ваші очки: <?= $score ?> | Знищено кораблів: <?= $shipsDestroyed ?>/10</p>
    <div class="grid">
        <!-- Верхні координати -->
        <div class="col-label"></div>
        <?php for ($col = 0; $col < $gridSize; $col++): ?>
            <div class="col-label"><?= $col ?></div>
        <?php endfor; ?>

        <!-- Гра -->
        <?php for ($row = 0; $row < $gridSize; $row++): ?>
            <!-- Ліві координати -->
            <div class="row-label"><?= $row ?></div>
            <?php for ($col = 0; $col < $gridSize; $col++): ?>
                <div class="cell 
                    <?php
                        if ($enemyGrid[$row][$col] === 2) echo 'hit';
                        elseif ($enemyGrid[$row][$col] === 3) echo 'miss';
                    ?>" 
                    onclick="shoot(<?= $row ?>, <?= $col ?>)">
                </div>
            <?php endfor; ?>
        <?php endfor; ?>
    </div>
    <form method="POST">
        <button type="submit" name="reset" value="true">Заново</button>
    </form>
    <p><?= htmlspecialchars($message) ?></p>

    <script>
        function shoot(row, col) {
            var form = document.createElement('form');
            form.method = 'POST';
            var rowInput = document.createElement('input');
            rowInput.type = 'hidden';
            rowInput.name = 'row';
            rowInput.value = row;
            form.appendChild(rowInput);
            var colInput = document.createElement('input');
            colInput.type = 'hidden';
            colInput.name = 'col';
            colInput.value = col;
            form.appendChild(colInput);
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>
</html>