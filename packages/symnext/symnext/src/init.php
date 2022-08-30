<?php

namespace Symnext;

define('Symnext\\SRC', __DIR__);
define('Symnext\\BOOT', SRC . '/Boot');
define('Symnext\\TEMPLATES', SRC . '/Templates');
define('Symnext\\SECTION_FIELDS', SRC . '/SectionFields');

stream_wrapper_register('sn-memory', 'Symnext\Stream\SNMemoryStream');
