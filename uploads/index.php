<?php
// Security: Prevent directory listing
header('HTTP/1.0 403 Forbidden');
exit('Directory access is forbidden.');
?>