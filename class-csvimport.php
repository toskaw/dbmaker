<?php
if ( !class_exists( "Stream_Filter_Mbstring" ) ) {
	require_once 'lib/Mbstring.php';
}

$ret = stream_filter_register( "convert.mbstring.*", "Stream_Filter_Mbstring" );
class DBM_Csv_import {

	private $fileobj;
	private $charset;
	private $path;
	private $filter;

	private function file() {
		if ( empty( $this->fileobj ) ) {
			if ( $this->charset != 'pass' && $this->charset != 'UTF-8' ) {
				$this->filter = 'php://filter/read=convert.mbstring.encoding.' 
					. $this->charset . ':UTF-8/resource='. $this->path;
			}
			else {
				$this->filter = $this->path;
			}

			$this->fileobj = new SplFileObject( $this->filter );
			$this->fileobj->setFlags(
				//SplFileObject::READ_AHEAD |
				//SplFileObject::SKIP_EMPTY |
				SplFileObject::READ_CSV 
				//SplFileObject::DROP_NEW_LINE
			);
		}
		return $this->fileobj;
	}

	public function __construct( $path ) {
		$this->path = $path;
	}

	public function set_input_charset( $charset ) {
		$this->charset = $charset;
	}

	public function seek( $line ) {
		$this->file()->seek( $line );
	}

	public function readline() {
		$data = $this->file()->current();
		$this->file()->next();
		return $data;
	}

	public function eof() {
		return $this->file()->eof();
	}

	public function key() {
		return $this->file()->key();
	}
}

?>