<?php
require_once('tcpdf/config/lang/eng.php');
require_once('tcpdf/tcpdf.php');

class tpdf {

	/**
	 * @var TCPDF
	 */
	private $__pdf = null;

	/**
	 * @var string path to content file
	 */
	private $__htmlFile;

	/**
	 * @var string document name
	 */
	private $__name = 'document.pdf';

	private $__params = array();

	public function __construct() {
		// create new PDF document
		$this->__pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
	}

	/**
	 * @return TCPDF
	 */
	public function getPdf() {
		return $this->__pdf;
	}

	/**
	 * Set path to file (html) with content
	 * @param string $pathToFile
	 * @throws Exception
	 */
	public function setHtmlFile($pathToFile) {
		try{
			if (!is_file($pathToFile) || !file_exists($pathToFile)) {
				throw new Exception('Error to find file: ' . $pathToFile);
			}
		} catch (Exception $e) {
			echo $e->getMessage();
			exit;
		}

		$this->__htmlFile = $pathToFile;
	}

	/**
	 * @return string
	 * @throws Exception
	 */
	private function __getFileContents() {
		if (!$this->__htmlFile) {
			throw new Exception('Not defined content file!');
		}

		$html = @file_get_contents($this->__htmlFile);
		if (!$html) {
			throw new Exception('Error to read file: ' . $this->__htmlFile);
		}

		return $html;
	}

	/**
	 * @param string $name
	 */
	public function setDocumentName($name) {
		if (!empty($name)) {
			$this->__name = $name;
		}
	}

	/**
	 * Bind document params
	 * @param array $params
	 */
	public function bindParams(array $params) {
		$this->__params = $params;
	}

	private function __bindParams($html) {
		if (!empty($this->__params)) {
			foreach ($this->__params as $key => $value) {
				$html = str_replace($key, $value, $html);
			}
		}
		return $html;
	}

	/**
	 * Create PDF document
	 */
	public function createDocument() {
		$this->__pdf->setPrintHeader(false);
		$this->__pdf->setPrintFooter(false);
		// set default monospaced font
		$this->__pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
		$this->__pdf->SetFontSize(11);
		//set margins
		$this->__pdf->SetMargins(PDF_MARGIN_LEFT, 20, PDF_MARGIN_RIGHT);
		//set auto page breaks
		$this->__pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
		// add a page
		$this->__pdf->AddPage();

		try {
			$html = $this->__getFileContents();
		} catch (Exception $e) {
			echo $e->getMessage();
			exit;
		}

		$html = $this->__bindParams($html);

		// output the HTML content
		$this->__pdf->writeHTML($html, false, 0, true, true);
		$this->__pdf->Output($this->__name, 'I');
	}
}