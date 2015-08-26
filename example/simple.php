<?php

error_reporting( E_ALL );
ini_set( 'display_errors', 'On' );

include_once '../vendor/autoload.php';

class ArrayCollection extends Dez\Collection\AbstractCollection {
    public function add( $item ) {
        $this->items[]  = $item;
    }
}

print_r( new ArrayCollection() );