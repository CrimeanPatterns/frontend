<?php

define('CHARTAPI', 'https://chart.googleapis.com/chart');
define('PIECHART_FLAT', 'p');
define('PIECHART_3D', 'p3');
define('PIECHART_MULTI', 'pc');

class PieChart {
	private $values;
	private $labels;
	private $top;
	private $apiURL;
	private $type;
	private $colors;
	private $width;
	private $height;
	private $originaldata;

	const CHART_TYPE = 'cht';
	const CHART_VALUES = 'chd';
	const CHART_LABELS = 'chdl';
	const CHART_SIZE = 'chs';
	const CHART_COLOR = 'chco';

	function __construct() {
		$this->values = array();
		$this->labels = array();
		$this->colors = array();
		$this->originaldata = array();
		$this->top = -1;
		$this->apiURL = CHARTAPI;
		$this->type = PIECHART_3D;
		$this->width = 400;
		$this->height = 250;
	}

	function setData(array $data) {
		$this->originaldata = $data;
	}

	private function getDataFromArray(array $row) {
		$this->addOneRow($row['Caption'],  $row['Value']);
	}

	private function addOneRow($label, $value) {
		$this->values[] = $value;
		$this->labels[] = $label;
	}

	function setMaxShowData($top) {
		$this->top = $top;
	}

	private static function  sortFunc($lft, $rht) {
		return -bccomp($lft['Value'], $rht['Value']);
	}

	private function buildData() {
		usort($this->originaldata, "PieChart::sortFunc");
		$dataLength = count($this->originaldata);
		$top = min($this->top, $dataLength);
		if($top < $dataLength && $top != -1) {
			$values = array_slice($this->originaldata, 0, $top);
			$other = array_slice($this->originaldata, $top);
			foreach($values as $value) {
				$this->getDataFromArray($value);
			}
			$otherSum = 0;
			foreach($other as $value) {
				$otherSum = bcadd($otherSum,  $value["Value"]);
			}
			$this->addOneRow('Other', $otherSum);
		}
		else {
			foreach($this->originaldata as $value) {
				$this->getDataFromArray($value);
			}
		}
	}

	function buildURL() {
		$this->buildData();
		$values = $this->normalizeData();
		$labels = implode('|', $this->labels);
		$values = 't:'.implode(',', $values);
		$query = array(
						PieChart::CHART_TYPE => $this->type,
						//PieChart::CHART_LABELS => $labels,
						PieChart::CHART_VALUES => $values,
						PieChart::CHART_SIZE => "{$this->width}x{$this->height}"
					);
		if(count($this->colors) > 0) {
			$colors = implode('|', $this->colors);
			$query[PieChart::CHART_COLOR] = $colors;
		}
		return $this->apiURL .'?'. urldecode(http_build_query($query));
	}

	function returnAsImage() {
		return "<img style='width: {$this->width}px; height: {$this->height}px;' src='".$this->buildURL()."'/>";
	}

	private function normalizeData() {
		$normalizeValues = array();
		$total = 0;
		foreach($this->values as $value) {
			$total = bcadd($total, $value);
		}
		$count = count($this->values);
		for($i = 0; $i < $count; $i++) {
			$normalizeValues[] = bcmul(bcdiv($this->values[$i], $total, 2), 100);
		}
		return $normalizeValues;
	}

	function setChartType($type) {
		$this->type = $type;
	}

	function setColors(array $colors) {
		$this->colors = $colors;
	}

	function setSize($width, $height) {
		$this->width = $width;
		$this->height = $height;
	}
}

?>
