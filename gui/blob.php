<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/codemirror.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/codemirror.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/xml/xml.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/css/css.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/javascript/javascript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/htmlmixed/htmlmixed.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/php/php.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/python/python.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/rust/rust.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/shell/shell.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/markdown/markdown.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/yaml/yaml.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/toml/toml.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/brainfuck/brainfuck.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/dockerfile/dockerfile.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/clike/clike.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/cmake/cmake.min.js"></script>

<?php
  $blob = \core\getBlob($repo, $request_path, $hash);
  $ext = pathinfo($request_path, PATHINFO_EXTENSION);
  $mime = \core\getMimeType($repo, $request_path, $hash);
  
  // CodeMirror doesn't like text/x-shellscript :|
  if($mime == 'text/x-shellscript') $mode = 'text/x-sh';

  // If the file info is being useless, try to match using
  // file extension data instead.
  else if($mime == 'application/octet-stream') {
    $mode = match($ext) {
      'png' => 'image/png',
      'webp' => 'image/webp',
      'jpg' => 'image/jpg',
      'jpeg' => 'image/jpg',
      default => $mime
    };
  }

  // Again, if the file info is being useless, try using
  // the extension :)
  else if($mime == 'text/plain') {
    $mode = match($ext) {
      'json' => 'application/json',
      'jsonld' => 'application/ld+json',
      'ts' => 'application/typescript',
      default => $ext
    };

    if(str_ends_with($ext, 'js')) $mime_type = 'text/javascript';
    if(str_ends_with($ext, 'html')) $mime_type = 'text/html';
  }

  else {
    $mode = $mime;
  }
?>

<div class="container blob">
  <?php if(str_starts_with($mime, 'image/')): ?>
    <img src="data:<?= $mime ?>;base64,<?= base64_encode($blob) ?>">
  <?php else: ?> 
    <pre class="code"><code><?= htmlspecialchars($blob) ?></code></pre>

    <script>
      // Mime-Type: <?= $mime ?>
      // Extension: <?= $ext ?>

      const codeElement = document.querySelector(".code");
      const containerElement = document.querySelector(".blob");

      CodeMirror(containerElement, {
        value: codeElement.innerText,
        mode:  "<?= $mode ?>",
        indentUnit: 2,
        lineWrapping: false,
        lineNumbers: true,
        readOnly: true,
        dragDrop: false,
        spellcheck: false,
        autocorrect: false,
        viewportMargin: Infinity
      });

      codeElement.remove();
      delete codeElement;
    </script>
  <?php endif; ?>
</div>
