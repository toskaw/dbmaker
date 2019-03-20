<?php

class DBM_Csv_option {
	private $post_type;
	private $format;
	private $rawformat;
	private $ignore_first_line;
	private $status;
	private $charcode;
	private $id;
	private $public_post_type;

	public static function get_posttype_list( $reload = false ) {
		static $types = array();

		if ( $reload ) {
			unset( $types );
		}

		if ( empty( $types ) ) {
			$options = array(
				'post_type' => 'csv',
				'fields' => 'ids', // retrieve only ids
			);
			$query = new WP_Query();
			$ids = $query->query( $options );
			foreach ( $ids as $id ) {
				$types[] = get_post_meta( $id, 'save_post_type', true );
			}
		}
		return $types;
	}

	public static function getInstance( $posttype, $reload = false ) {
		static $instance = array();
		if ( $reload ) {
			unset( $instance );
		}
		if ( !isset( $instance[$posttype] ) ) {
			$instance[$posttype] = new self( $posttype );
		}
		return $instance[$posttype];
	}

	private function __construct( $posttype ) {
		$this->post_type = $posttype;
		$options = array(
			'post_type' => 'csv',
			'meta_key' => 'save_post_type',
			'meta_value' => $posttype,
			'fields' => 'ids', // retrieve only ids
		);
		$query = new WP_Query();
		$params = $query->query( $options );
		if ( $params ) {
			$option_id = $params[0];
			$work = get_post_meta( $option_id, "format", true );
			$this->format = explode( ",", $work );
			$this->rawformat = $work;
			$this->status = get_post_meta( $option_id, "status", true );
			$this->charcode = get_post_meta( $option_id, "char_code", true );
			$this->ignore_first_line = get_post_meta( $option_id, "ignore_firstline", true );
			$this->id = $option_id;
			$this->public_post_type = get_post_meta( $option_id, "public_post_type", true );
		}
		else {
			$this->post_type = false;
		}
	}

	public function post_type() {
		return $this->post_type;
	}

	public function format( $index ) {
		return $this->format[$index];
	}

	public function format_array() {
		return $this->format;
	}

	public function rawformat() {
		return $this->rawformat;
	}

	public function status() {
		return $this->status;
	}

	public function charcode() {
		return $this->charcode;
	}

	public function ignore_first_line() {
		return $this->ignore_first_line;
	}

	public function id() {
		return $this->id;
	}
	public function get_taxonomys() {
		$taxonomys = array();
		foreach ( $this->format as $item ) {
			if ( substr($item, 0, 4) == 'tax_' ) {
				$taxonomys[] = substr( $item, 4 );
			}
		}
		return $taxonomys;
	}

	public function public() {
		return $this->public_post_type == '1';
	}
}

?>