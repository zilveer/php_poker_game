<?php

session_start();

//check to see if this is first time here, get session variables if they exist
if (isset($_SESSION['cash'])) {
	$cash = $_SESSION['cash'];
	$cards = $_SESSION['cards'];
	$secondDeal = $_SESSION['secondDeal'];
}

if (empty($cash)){

	$cash = 100;
	$cards = array();
	$hand = array();
	$keepIt = array();
	$secondDeal = FALSE;
}

if ($secondDeal == FALSE) {
	makeTheDeck();
}

dealTheDeck();

if ($secondDeal == TRUE) {
	print "<h2>Second Deal</h2>\n";
}
else {
	print "<h2>First Deal</h2>\n";
}

printTheStuff();

$_SESSION['cash'] = $cash;
$_SESSION['cards'] = $cards;
$_SESSION['secondDeal'] = $secondDeal;

// create and shuffle the deck of cards
function makeTheDeck() {
	global $cards;
	$cards = array();
	$faces = array('2', '3', '4', '5', '6', '7', '8', '9', 'T', 'J', 'Q', 'K', 'A');
	$suits = array('h', 'd', 'c', 's');
	// $cards = array();
	foreach($faces as $face) {
		foreach($suits as $suit) {
			$cards[] = $face . $suit;
		}
	}
	shuffle($cards);
	// error checking - make sure deck has been created properly
	//	print "Initial deck: <br />";
	//	echo implode(', ', $cards);
	//	print "<br />";
}

//deal the cards
function dealTheDeck() {
	global $cards;
	global $hand;
	global $secondDeal;
	$hand = array();
	if($secondDeal == FALSE) {
		for ($count = 0; $count < 5; $count++) {
			$hand[] = array_pop($cards);
		}
	}
	else {
		if (isset($_POST['keepIt'])) { // keep cards selected from first deal, if any
			$keepIt = $_POST['keepIt'];
			foreach($keepIt as $card) {
				$hand[] = $card;
			}
			$cardsToDeal = 5 - count($keepIt);
			for ($count = 0; $count < $cardsToDeal; $count++) {
				$hand[] = array_pop($cards);
			}
		}
		else {
			for ($count = 0; $count < 5; $count++) {
			$hand[] = array_pop($cards);
			}
		}
	}
}

function printTheStuff() {
	global $cards;
	global $hand;
	global $secondDeal;
	global $cash;
	// print the hand, checkboxes if first deal, and 'deal' button
	print "<form action='index.php' method='post'>";
	if ($secondDeal == FALSE) {
		print "<table><tr>";
			foreach($hand as $card){
				print("<th style='height:120'><img src='img\/" . $card . ".gif' width='73' height='97' /><br /><input type='checkbox' name='keepIt[]' value='" . $card . "'/></th>");
			}
		print "</tr></table>";
		print "<input type='submit' value='Deal' /> (check the box below your card if you wish to keep it)<br />";
		$secondDeal = TRUE;
	}
	else {
		print "<table><tr>";
			foreach($hand as $card){
				print("<th style='height:120'><img src='img\/" . $card . ".gif' width='73' height='97' /></th>");
			}
		print "</tr></table>";
		print "<input type='submit' value='Deal' />";
		$secondDeal = FALSE;
		evaluateTheHand();
	}
	print "</form>";
	print "<br />Cash: &#36;" . $cash;
	
	// error checking - make sure deck is updating properly
	//	print "<br />Deck after current deal: <br />";
	//	echo implode(', ', $cards);
}

function evaluateTheHand(){
	global $hand;
	global $cash;	
	$cardNum = array();
	$cardSuit = array();
	$numPairs = 0;
	$numThrees = 0;
	$numFours = 0;
	$numStraights = 0;
	$numFullHouses = 0;
	$numFlushes = 0;
	$payoff = 0;
	$cash -= 5; //subtract initial bid
	foreach($hand as $card){ // load the card information into arrays for evaluation
		$cardNum[] = substr($card, 0, 1);
		$cardSuit[] = substr($card, 1, 1);
	}
	
	// error checking - make sure cards are reading into arrays properly
	//	print "<p>Deck arrays: </p>";	
	//	foreach($cardNum as $num){
	//		print $num;
	//	}
	//	print "<br />";
	//	foreach($cardSuit as $suit){
	//		print $suit;
	//	}
	
	$numOccurences = array_count_values($cardNum);
	//check for pairs, three- and four-of-a-kind
	foreach($numOccurences as $cardVal => $cardCount){
		switch ($cardCount) {
			case 4:
				$numFours++;
				break;
			case 3:
				$numThrees++;
				break;
			case 2:
				$numPairs++;
				break;
		}
	}
	//check for a flush
	$suitOccurences = array_count_values($cardSuit);
	if (count($suitOccurences) == 1) {
		$numFlushes++;
	}	
	//check for a straight
	foreach($cardNum as $index => $value){ //convert letters into numbers to make this easier
		if ($value == 'T'){
			$cardNum[$index] = 10;
		}
		if ($value == 'J'){
			$cardNum[$index] = 11;
		}
		if ($value == 'Q'){
			$cardNum[$index] = 12;
		}
		if ($value == 'K'){
			$cardNum[$index] = 13;
		}
		if ($value == 'A'){
			$cardNum[$index] = 1;
		}
	}
	sort($cardNum);
	if ((($cardNum[0] + 1) == $cardNum[1]) && (($cardNum[1] + 1) == $cardNum[2]) && (($cardNum[2] + 1) == $cardNum[3]) && (($cardNum[3] + 1) == $cardNum[4])) {
		$numStraights++;
	}
	else if ($cardNum[0] == 1 && $cardNum[1] == 10 && $cardNum[2] == 11 && $cardNum[3] == 12 && $cardNum[4] == 13){
		$numStraights++;
	}
	// tell player if they won anything, award winnings
	print "<p>";
	if ($numFlushes == 1) {
		print "You have a flush! <br />";
		$payoff += 10;
	}
	if ($numStraights == 1) {
		print "You have a straight! <br />";
		$payoff += 18;
	}
	else if ($numFours == 1) {
		print "You have four of a kind! <br />";
		$payoff += 16;
	}
	else if ($numPairs == 1 && $numThrees == 1){
		print "You have a full house! <br />";
		$payoff += 12;
	}
	else if ($numThrees == 1) {
		print "You have three of a kind! <br />";
		$payoff += 6;
	}
	else if ($numPairs == 2){
		print "You have two pairs! <br />";
		$payoff += 6;
	}
	else if ($numPairs == 1){
		print "You have a pair! <br />";
		$payoff += 4;
	}
	print "</p><p>You bet &#36;5<br />\n" . "You won &#36;" . $payoff . "<br />\n";
	$cash += $payoff;
}

?>