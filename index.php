<?php
/**
 * Created by PhpStorm.
 * User: peyman
 * Date: 2/22/18
 * Time: 10:55 PM
 */

/**
 * @param int $index
 * @param int $rows
 * @param int $columns
 * @return array
 */
function GetNeighbourIndexes($index, $rows, $columns) {
	$neighbours = [];
	if ($index % $columns >= 1) {
		$neighbours[] = $index - 1;
		if ($index > $columns) {
			$neighbours[] = $index - 11;
		}
		if ($index < ($rows * ($columns-1))) {
			$neighbours[] = $index + 9;
		}
	}
	if ($index % $columns !== 9) {
		$neighbours[] = $index + 1;
		if ($index > $columns) {
			$neighbours[] = $index - 9;
		}
		if ($index < ($rows * ($columns-1))) {
			$neighbours[] = $index + 11;
		}
	}
	if ($index > $columns) {
		$neighbours[] = $index - 10;
	}
	if ($index < ($rows * ($columns-1))) {
		$neighbours[] = $index + 10;
	}
	return $neighbours;
}

function FindConnectedNeighbours($index, $board, $rows, $columns, $founded) {
	$neighbours = GetNeighbourIndexes($index, $rows, $columns);
	foreach ($neighbours as $neighbour) {
		if (!in_array($neighbour, $founded)) {
			if ($board[$neighbour][0] === 0) {
				$founded[] = $neighbour;
				$founded = FindConnectedNeighbours($neighbour, $board, $rows, $columns, $founded);
			}
		}
	}
	return $founded;
}

/**
 * @param int $start_index
 * @param int $rows
 * @param int $columns
 * @return array
 */
function SetupNewBoard($start_index, $rows, $columns, $mines) {
	$board = [];
	$mine_indexes = [];
	$total = $rows * $columns;
	for ($i = 0; $i < $total; $i++) {
		$mine_indexes[$i] = $i;
		$board[$i] = [0, 0];
	}
	for ($i = 0; $i < $total; $i++) {
		$swap_from = rand(0, $total-1);
		$swap_to = rand(0, $total-1);
		$temp = $mine_indexes[$swap_to];
		$mine_indexes[$swap_to] = $mine_indexes[$swap_from];
		$mine_indexes[$swap_from] = $temp;
	}
	$mines_created = 0;
	for ($i = 0; $i < $mines; $i++) {
		if ($mine_indexes[$i] != $start_index) {
			$board[$mine_indexes[$i]] = [1, 0];
			$mines_created++;
		}
	}
	if ($mines_created < $mines) {
		$board[$mine_indexes[$mines]] = [1, 0];
	}

	for ($i = 0; $i < $total; $i++) {
		if ($board[$i][0] !== 1) {
			$neighbours = GetNeighbourIndexes($i, $rows, $columns);
			$mines_count = 0;
			foreach ($neighbours as $neighbour) {
				if ($board[$neighbour][0] === 1) {
					$mines_count++;
				}
			}
			if ($mines_count > 0) {
				$board[$i][0] = $mines_count + 1;
			}
		}
	}

	return $board;
}

/**
 * @param array $board
 * @param int $button_index
 * @param int $rows
 * @param int $columns
 * @return array
 */
function SelectCellAtIndex($board, $button_index, $rows, $columns) {
	$cell = $board[$button_index];
	$cell[1] = 1;
	if ($cell[0] === 0) {
		$empty_cells = FindConnectedNeighbours($button_index, $board, $rows, $columns, [$button_index]);
		foreach ($empty_cells as $empty_cell) {
			$board[$empty_cell][1] = 1;
		}
	}
	$board[$button_index] = $cell;
	return $board;
}

/**
 * @param array $board
 * @return array
 */
function CheckGameState($board) {
	$ended = false;
	$winner = false;
	$remaining_empty_cells = 0;
	foreach ($board as $cell) {
		if (!$ended) {
			if ($cell[1] === 1) {
				if ($cell[0] === 1) {
					$ended = true;
					$winner = false;
				}
			} else {
				if ($cell[0] !== 1) {
					$remaining_empty_cells++;
				}
			}
		}
	}
	if ($remaining_empty_cells === 0 && !$ended) {
		$ended = true;
		$winner = true;
	}
	return [$ended, $winner];
}

?>
<html>
	<head>
	</head>
	<body>
<?php
if (isset($_GET['params'])) {
	$board_params = $_GET['params'];
	$board_params = explode('x', $board_params);
	if ( count($board_params) !== 3 || !is_numeric($board_params[0]) || !is_numeric($board_params[1]) || !is_numeric($board_params[2])) {
		echo 'Error board params should be with format: [rows]x[columns]x[mines]';
		die();
	}

	$rows = intval($board_params[0]);
	$columns = intval($board_params[1]);
	$mines = intval($board_params[2]);
	if ($mines > $rows * $columns) {
		echo 'Mines count should be less than total cells!';
		die();
	}
	$board = [];
	$state = [false, false];
	if (isset($_POST['board'])) {
		$board = json_decode($_POST['board']);
		if (isset($_POST['button'])) {
			if ($_POST['button'] >= 0) {
				if (count($board) === 0) {
					$board = SetupNewBoard($_POST['button'], $rows, $columns, $mines);
					$board = SelectCellAtIndex($board, $_POST['button'], $rows, $columns);
				} else {
					$board = SelectCellAtIndex($board, $_POST['button'], $rows, $columns);
				}

				$state = CheckGameState($board);
			}
		}
	}
	?>
	<form action="index.php?params=<?php echo $_GET['params']; ?>" method="post">
	<input type="hidden" value="<?php echo json_encode($board); ?>" name="board">
	<input type="hidden" value="-1" id="button_index" name="button">
	<?php
	$indexer = 0;
	for ($i = 0; $i < $rows; $i++) {
		?><div><?php
		for ($j = 0; $j < $columns; $j++) {
			$label = '&nbsp';
			$color = 'black';
			$background = 'white';
			if (isset($board[$indexer])) {
				if ($board[$indexer][1] === 1 || $state[0]) {
					if ($board[$indexer][0] === 1) {
						$label = 'B';
						$background = 'red';
						$color = 'white';
					} else if ($board[$indexer][0] > 0) {
						$label = intval($board[$indexer][0]-1);
						if ($label <= 2) {
							$color = 'green';
						} else if ($label <= 4) {
							$color = 'blue';
						} else if ($label <= 6) {
							$color = 'orange';
						} else if ($label <= 8) {
							$color = 'red';
						}
					} else {
						$background = 'lightgray';
					}
				}
			}
			?>
			<button style="border: 1px black solid;width: 40px; height: 40px; margin: 5px; padding: 0px; color: <?php echo $color?>; background-color: <?php echo $background; ?>" onclick="SetButtonIndex('<?php echo $indexer; ?>')">
				<?php echo $label; ?>
			</button>
			<?php
			$indexer++;
		}
		?><br/></div><?php
	}
	?></form><?php
	if ($state[0]) {
		if ($state[1]) {
			?>
			<label style="color: green; font-weight: bold;">Congratulations, YOU WON :p</label>
			<?php
		} else {
			?>
			<label style="color: red; font-weight: bold;">SORRY, YOU LOST :(</label>
			<?php
		}
		?>
		<br/>
		<form action="index.php?params=<?php echo $_GET['params']; ?>" method="post" style="display: inline;"><button>RESTART</button></form>
		<form action="index.php" style="display: inline;" method="post"><button>NEW GAME</button></form>
		<?php
	}
} else {
	if (isset($_POST['rows']) && isset($_POST['columns']) && isset($_POST['mines'])) {
		ob_start();
		header('Location: '.'index.php?params=' . $_POST['rows'] . 'x' . $_POST['columns'] . 'x' .$_POST['mines']);
		ob_end_flush();
		die();
	}
	?>
	<form action="index.php" method="post">
		<label>Rows</label>
		<input placeholder="Board rows count" type="number" name="rows"><br/>
		<label>Columns</label>
		<input placeholder="Board columns count" type="number" name="columns"><br/>
		<label>Mines</label>
		<input placeholder="Mines count" type="number" name="mines"><br/>
		<button>Start</button>
	</form>
	<?php
}
?>
		<script>
		    var SetButtonIndex = function(index) {
		        var button_input = document.getElementById("button_index");
		        button_input.value = index;
		    }
		</script>
	</body>
</html>

