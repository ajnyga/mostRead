<?php

/**
 * @defgroup plugins_blocks_mostRead Most Read block plugin
 */

/**
 * @file plugins/blocks/mostRead/index.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_blocks_mostRead
 * @brief Wrapper for "Most Read" block plugin.
 *
 */

require_once('MostReadBlockPlugin.inc.php');

return new MostReadBlockPlugin();

?>
