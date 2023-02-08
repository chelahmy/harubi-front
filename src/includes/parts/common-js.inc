<script src="<?= $rpath ?>libs/bootstrap-5.2.2-dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $rpath ?>libs/jquery-3.6.1.min.js"></script>
<?php if (isset($has_discussion) && $has_discussion === TRUE) : ?>
<script src="<?= $rpath ?>libs/linkifyjs/linkify.min.js"></script>
<script src="<?= $rpath ?>libs/linkifyjs/linkify-string.min.js"></script>
<script src="<?= $rpath ?>libs/linkifyjs/linkify-plugin-mention.min.js"></script>
<script src="<?= $rpath ?>libs/linkifyjs/linkify-plugin-hashtag.min.js"></script>
<script src="<?= $rpath ?>libs/viewerjs-1.11.2/dist/viewer.js"></script>
<script src="<?= $rpath ?>libs/video-js-7.20.3/video.min.js"></script>
<script src="<?= $rpath ?>libs/leaflet/leaflet.js"></script>
<script src="<?= $rpath ?>libs/Leaflet.fullscreen-1.0.2/dist/Leaflet.fullscreen.js"></script>
<?php endif; ?>
<script src="<?= $rpath ?>js/common.js" type="text/javascript"></script>
<?php if (isset($has_discussion) && $has_discussion === TRUE) : ?>
<script src="<?= $rpath ?>js/simplemap.js" type="text/javascript"></script>
<script src="<?= $rpath ?>js/discussion.js" type="text/javascript"></script>
<?php endif; ?>
<?php if (!isset($default_common) || $default_common === FALSE) : ?>
<script src="js/common.js" type="text/javascript"></script>
<?php endif; ?>

