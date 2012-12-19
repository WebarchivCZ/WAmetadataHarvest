<?php

namespace Access;

interface Provider
{

	function getAccess($resource = NULL);

}