<?php
/**
 * Twig Session plugin for Craft CMS
 *
 * Twig Session Variable
 *
 * @author    Dilli Labs
 * @link      https://www.dillilabs.com
 * @package   TwigSession
 * @since     1.0.0
 */

namespace Craft;

class TwigSessionVariable
{
	public function add($key, $value)
	{
	    craft()->httpSession->add($key, $value);
	}

	public function get($key)
	{
	    return craft()->httpSession->get($key);
	}
}