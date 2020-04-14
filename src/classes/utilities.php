<?php
ini_set ('memory_limit', '-1');
ini_set ('max_execution_time', 2400); //40 minutes
ini_set ('display_errors', 1);
error_reporting(E_ALL);

class Utilities {
	private $debugEnabled = true;
	private $debugLog = "log/debug.log";
	/*
	 * HTML table generator component
	 */
	public function outputHeadingsToTable($result, $exclude) {
		$headingArray = $this->getHeadingsFromResults($result);
		$heading = "";
		foreach($headingArray as $headingVal) {
			if (!in_array($headingVal, $exclude))
				$heading .= "<th>$headingVal</th>";
		}

		return $heading;
	}
	/*
	 *
	 */
	public function convertMySQLOutputToArray($result) {
		$returnArray = [];
		if (is_object($result)) {
			while ($row = $result->fetch_assoc()) {
				$returnArray[] = $row;
			}
			//set pointer back
			mysqli_data_seek($result, 0);
			return $returnArray;
		}
		return $result;
	}
	/*
	 * HTML table generator component - will work with associative array or mysql result set
	 * Expects $result to either be an associative array or a database return object, eg:
	 *
	 */
	public function outputContentsToTable($result, $exclude) {
		$contents = "";
		$recordID = 0;
		$counter = 0;
		$rawData = [];
		$headingArray = $this->getHeadingsFromResults($result);
		$contentsArray = $this->convertMySQLOutputToArray($result);

		foreach($contentsArray as $row) {
			$rawData[] = $row;
			$contents .= "<tr>";
			if (($row['id'] != $recordID) && $counter != 0) {
				$contents .= "<tr><td colspan=" . count($headingArray) . " align='center'>-----------------------------------------------------------------------------------------</td></tr>";
			}
			foreach ($headingArray as $headingKey => $headingVal) {
				if (!in_array($headingVal, $exclude)) {
					$contents .= "<td>$row[$headingVal]</td>";
				}
			}
			$contents .= "</tr>";
			$recordID = $row['id'];
			$counter++;
		}
		return ['html' => $contents, 'rawData' => $rawData];
	}
	/*
	 * HTML table generator component
	 */
	function getHeadingsFromResults($result) {
		if (!is_array($result)) {
			$row = $result->fetch_assoc();
			mysqli_data_seek($result, 0);
			return array_keys($row);
		} else {
			return array_keys($result[0]);
		}
	}
	/*
	* HTML dropdown generator
	*/
	function generateComboBox($selectData, $selectName, $additionalOptions = '') {
		$output = "<select name=$selectName>";
		$output .= "<option value='all'>All</option>";
		$output .= $additionalOptions;
		$selectedOption = isset($_REQUEST[$selectName]) ? $_REQUEST[$selectName] : 0;
		while ($row = $selectData->fetch_assoc()) {
			$selected = $selectedOption == $row['id'] ? 'selected' : '';
			$output .= "<option value='" . $row['id'] . "' $selected>" . $row['name'] . "</option>";
		}
		$output .= "</select>";
		return $output;
	}
	/*
	 *
	 */
	function debug($query, $result, $description) {
		$stackTrace = debug_backtrace();
		$query = str_replace("\n", "<br>", $query);
		$msg = "<br><b>Function: </b>" . $stackTrace[2]['function'];
		$msg .= "<br><b>Description: </b>" . $description;
		$msg .= "<br><b>Query: </b><br>" . $query;
		$msg .= "<b>Results: </b><br>";
		if (is_bool($result)) {//in case if inserts, deletes or updates
			$msg .= 'Item successfully processed';
		} else {//in case of selects
			while ($row = $result->fetch_assoc()) {
				$msg .= print_r($row, true);
				$msg .= "<br>";
			}
			mysqli_data_seek($result, 0);
		}
		$msg .= "<hr><br>";
		$msg = str_replace("<br>", "\n", $msg);
		$msg = str_replace("<hr>", "---------------------------------------------", $msg);
		$msg = str_replace("<b>", "", $msg);
		$msg = str_replace("</b>", "", $msg);
		$this->log($msg, 'INFO');
	}
	/*
	 *
	 */
	function br2nl($string) {
		return preg_replace('/\<br(\s*)?\/?\>/i', PHP_EOL, $string);
	}

	/*
	 *
	 */
	function mapParams() {
		if (isset($argv))
			if (count($argv)) {
				foreach ($argv as $value)
					$_REQUEST[$value] = 1;
			}
	}

	/*
	*
	*/
	function cleanInput($inputValue, $wrapQuote = true)  {
		if (is_array($inputValue)) {
			foreach($inputValue as $key => $field) {
				$inputValue[$key] = $this->sanitize($field, $wrapQuote);
			}
		}
		return $inputValue;
	}
	/*
	 *
	 */
	private function sanitize($inputValue, $wrapQuote) {
		$inputValue = filter_var($inputValue, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		//strip all user-provided slashes, trim whitespace, make lowercase, then add slashes again to escape '
		$inputValue = addslashes(strtolower(trim(stripslashes($inputValue))));
		return $wrapQuote ? "'" . $inputValue . "'" : $inputValue;
	}
	/*
	 *
	 */
	function getFileToProcess() {
		$fileToProcess = reset($_FILES);
		if (move_uploaded_file($fileToProcess['tmp_name'], $this->inputFileLocation.$fileToProcess['name'])) {
			$response = ['success' => 'true',
				'payload' => $this->inputFileLocation.$fileToProcess['name']];
		} else {
			$response = ['success' => 'false',
				'payload' => 'File failed to copy'];
		}
		return $response;
	}

	/*
	 *
	 */
	function log($msg, $type) {
		if (($this->debugEnabled || $_REQUEST['debug']) && $type == 'ERROR') {
			$logMsg = date("Y-m-d H:i:s") . ": " . $type . " - " . $msg . "<br>";
			file_put_contents($this->debugLog, str_replace("<br>", "\r", $logMsg), FILE_APPEND);
//        print_r($logMsg);
//        print_r($data);
		}
	}
}