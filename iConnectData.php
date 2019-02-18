<?php
	namespace Connect;
	interface iConnectData{
		public function execute_query($query, $params = array());
	}
?>