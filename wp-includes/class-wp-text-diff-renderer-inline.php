<?php
 #[AllowDynamicProperties]
 class WP_Text_Diff_Renderer_inline extends Text_Diff_Renderer_inline { public function _splitOnWords( $string, $newlineEscape = "\n" ) { $string = str_replace( "\0", '', $string ); $words = preg_split( '/([^\w])/u', $string, -1, PREG_SPLIT_DELIM_CAPTURE ); $words = str_replace( "\n", $newlineEscape, $words ); return $words; } } 