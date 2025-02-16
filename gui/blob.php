<div class="container blob">
  <?php $blob = \core\getBlob($repo, $request_path, $hash) ?>
  <pre><code><?= htmlspecialchars($blob) ?></code></pre>
</div>
