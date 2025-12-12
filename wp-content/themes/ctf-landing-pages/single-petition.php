<?php
/**
 * Single Petition Template
 * Thin wrapper that loads petition content partial.
 */

get_header('custom');
get_template_part('template-parts/content', 'petition');
get_footer('custom');
