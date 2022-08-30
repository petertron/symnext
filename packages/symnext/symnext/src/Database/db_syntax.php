<?php

App::Database()->insert(
    'sn_sections',
    values: ['title#handle' => $title_handle, 'title#value' => $title],
    on_duplicate_key_update: true
);

App::Database()->insert('sn_sections', [
    ['title#handle' => $title_handle, 'title#value' => $title],
    'on_duplicate_key_update' => true
]);
