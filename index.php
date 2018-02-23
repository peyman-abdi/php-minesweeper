<?php
/**
 * Created by PhpStorm.
 * User: peyman abdi
 */

const MIN_CELL = 5;
const MAX_CELL = 15;
const HIDDEN = 0;
const VIEW = 1;
const FLAG = 2;
const BLANK = 0;
const BOMB = 1;

class MineSweeper {
	public $rows, $columns;
	public $board;
	public $state;
	public $mines;

	/**
	 * MineSweeper constructor.
	 *
	 * @param int $rows
	 * @param int $columns
	 * @param int $mines
	 * @param array $board
	 */
	public function __construct($rows, $columns, $mines, $board) {
	    $this->rows = $rows;
	    $this->columns = $columns;
	    $this->board = $board;
	    $this->mines = $mines;
	}

	/**
	 * @param $index
	 * @return array
	 */
	function GetNeighbourIndexes($index) {
	    $rows = $this->rows;
	    $columns = $this->columns;
		$neighbours = [];
		if ($index % $columns >= 1) {
			$neighbours[] = $index - 1;
			if ($index > $columns) {
				$neighbours[] = $index - $columns - 1;
			}
			if ($index < ($rows * ($columns-1))) {
				$neighbours[] = $index + $columns - 1;
			}
		}
		if ($index % $columns !== ($columns - 1)) {
			$neighbours[] = $index + 1;
			if ($index > $columns) {
				$neighbours[] = $index - $columns + 1;
			}
			if ($index < ($rows * ($columns-1))) {
				$neighbours[] = $index + $columns + 1;
			}
		}
		if ($index > $columns) {
			$neighbours[] = $index - $columns;
		}
		if ($index < ($rows * ($columns-1))) {
			$neighbours[] = $index + $columns;
		}
		return $neighbours;
	}

	/**
	 * @param int $index
	 * @param array $founded
	 * @return array
	 */
	function FindConnectedNeighbours($index, $founded) {
		$board = $this->board;
		$neighbours = $this->GetNeighbourIndexes($index);
		foreach ($neighbours as $neighbour) {
			if (!in_array($neighbour, $founded)) {
				if ($board[$neighbour][0] === BLANK) {
					$founded[] = $neighbour;
					$founded = $this->FindConnectedNeighbours($neighbour, $founded);
				}
			}
		}
		return $founded;
	}

	/**
	 * @param int $start_index
	 */
	function SetupNewBoard($start_index) {
		$rows = $this->rows;
		$columns = $this->columns;
		$mines = $this->mines;
		$board = [];
		$mine_indexes = [];
		$total = $rows * $columns;
		for ($i = 0; $i < $total; $i++) {
			$mine_indexes[$i] = $i;
			$board[$i] = [BLANK, HIDDEN];
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
				$board[$mine_indexes[$i]] = [BOMB, HIDDEN];
				$mines_created++;
			}
		}
		if ($mines_created < $mines) {
			$board[$mine_indexes[$mines]] = [BOMB, HIDDEN];
		}

		for ($i = 0; $i < $total; $i++) {
			if ($board[$i][0] !== 1) {
				$neighbours = $this->GetNeighbourIndexes($i);
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

		$this->board = $board;
	}

	/**
	 * @param int $button_index
	 */
	function SelectCellAtIndex($button_index) {
		$board = $this->board;
		$cell = $board[$button_index];
		$cell[1] = VIEW;
		if ($cell[0] === BLANK) {
			$empty_cells = $this->FindConnectedNeighbours($button_index, [$button_index]);
			foreach ($empty_cells as $empty_cell) {
				$board[$empty_cell][1] = VIEW;
			}
		}
		$board[$button_index] = $cell;
		$this->board = $board;
	}

	/**
     * check for game ending with player as winner or loser
	 */
	function CheckGameState() {
	    $board = $this->board;
		$ended = false;
		$winner = false;
		$remaining_empty_cells = 0;
		if (count($board) === 0) {
		    return [false, false];
        }
		foreach ($board as $cell) {
			if (!$ended) {
				if ($cell[1] === VIEW) {
					if ($cell[0] === BOMB) {
						$ended = true;
						$winner = false;
					}
				} else {
					if ($cell[0] !== BOMB) {
						$remaining_empty_cells++;
					}
				}
			}
		}
		if ($remaining_empty_cells === 0 && !$ended) {
			$ended = true;
			$winner = true;
		}
		$this->state = [$ended, $winner];
	}

	/**
	 * @param int $button_index
     * @param boolean $flag
	 */
	function MakeMove($button_index, $flag = false) {
	    if (!$this->state[0]) {
		    $board = $this->board;
		    if (count($board) === 0) {
			    $this->SetupNewBoard($button_index);
			    $this->SelectCellAtIndex($button_index);
		    } else {
			    if ($flag && $board[$button_index][1] !== VIEW) {
				    if ($board[$button_index][1] === FLAG) {
					    $board[$button_index][1] = HIDDEN;
				    } else {
					    $board[$button_index][1] = FLAG;
				    }
				    $this->board = $board;
			    } else {
				    $this->SelectCellAtIndex($button_index);
			    }
		    }
        }
		$this->CheckGameState();
    }
}


/****************************************
 *                                      *
 * HTML + FORM Renderer for MineSweeper *
 *                                      *
 ****************************************/
?>
<html>
	<head>
	</head>
	<body>
<?php

if (isset($_GET['params'])) {
	/**
	 * check board size and mines count
	 */
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
	if ($rows < MIN_CELL || $rows > MAX_CELL || $columns < MIN_CELL || $columns > MAX_CELL) {
		echo 'Rows and Columns should be between 5 and 15';
		die();
	}

	/**
	 * create minesweeper and reset board if exists from post params
	 */
	$minesweeper = new MineSweeper($rows, $columns, $mines, []);
	if (isset($_POST['board'])) {
		$minesweeper->board = json_decode($_POST['board']);
		if (isset($_POST['button']) && isset($_POST['flag'])) {
			if ($_POST['button'] >= 0) {
			    $minesweeper->MakeMove($_POST['button'], $_POST['flag'] == 1);
			}
		}
	}
	$board = $minesweeper->board;
	$state = $minesweeper->state;

	/**
	 * render board
	 */
	?>
    <p>Left Click to reveal tile</p>
    <p>Right Click to mark the tile as BOMB!</p>
	<form action="index.php?params=<?php echo $_GET['params']; ?>" method="post" id="mainForm">
	<input type="hidden" value="<?php echo json_encode($board); ?>" name="board">
	<input type="hidden" value="-1" id="button_index" name="button">
    <input type="hidden" value="0" id="flag_mode" name="flag">
	<?php
	$indexer = 0;
	for ($i = 0; $i < $rows; $i++) {
		?><div><?php
		for ($j = 0; $j < $columns; $j++) {
			$label = '&nbsp';
			$color = 'black';
			$background = 'white';
			if (isset($board[$indexer])) {
			    if ($board[$indexer][1] === FLAG) {
			        $label = '&#9873;';
			        $background = 'orange';
			        $color = 'white';
                } else if ($board[$indexer][1] === VIEW || $state[0]) {
					if ($board[$indexer][0] === BOMB) {
						$label = '&#128163;';
						$background = $state[1] ? 'green':'red';
						$color = 'white';
					} else if ($board[$indexer][0] > BLANK) {
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
			<div style="text-align: center; display: inline-block; border: 1px black solid; width: 40px; height: 40px; margin: 5px; padding: 0px; color: <?php echo $color?>; background-color: <?php echo $background; ?>" oncontextmenu="SetFlagIndex('<?php echo $indexer; ?>'); return false;" onclick="SetButtonIndex('<?php echo $indexer; ?>')">
				<span style="line-height: 40px; display: inline-block; vertical-align: middle;"><?php echo $label; ?></span>
			</div>
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
	/**
	 * ask for board size and mines count, then redirect to playing state in index.php
	 */
	if (isset($_POST['rows']) && isset($_POST['columns']) && isset($_POST['mines'])) {
		ob_start();
		header('Location: '.'index.php?params=' . $_POST['rows'] . 'x' . $_POST['columns'] . 'x' .$_POST['mines']);
		ob_end_flush();
		die();
	}
	?>
	<form action="index.php" method="post">
		<label>Rows (min:<?php echo MIN_CELL ?>, max:<?php echo MAX_CELL ?>)</label>
		<input placeholder="Board rows count" type="number" name="rows"><br/>
		<label>Columns (min:<?php echo MIN_CELL ?>, max:<?php echo MAX_CELL ?>)</label>
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
		        var flag_mode = document.getElementById("flag_mode");
		        button_input.value = index;
		        flag_mode.value = 0;
		        document.getElementById("mainForm").submit();
		    };
		    var SetFlagIndex = function(index) {
                var button_input = document.getElementById("button_index");
                var flag_mode = document.getElementById("flag_mode");
                button_input.value = index;
                flag_mode.value = 1;
                document.getElementById("mainForm").submit();
            };
		</script>
	</body>
</html>

