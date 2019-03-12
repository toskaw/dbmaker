<?php
class DBM_Csv_import {

	private $file;
	private $charset;

	public function __construct($path) {
		$this->file = new SplFileObject($path);
		$this->file->setFlags(
			SplFileObject::READ_AHEAD |
			SplFileObject::SKIP_EMPTY |
			SplFileObject::DROP_NEW_LINE
		);
	}

	public function set_input_charset($charset) {
		$this->charset = $charset;
	}

	public function seek($line) {
		$this->file->seek($line);
	}

	public function readline() {
		$line = $this->file->current();
		$line = mb_convert_encoding($line, 'UTF-8', $this->charset);
		$data = str_getcsv($line);
		$this->file->next();
		return $data;
	}

	public function eof() {
		return $this->file->eof();
	}

	public function key() {
		return $this->file->key();
	}
}

?>