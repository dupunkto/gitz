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
  $mime = \core\detectMimeType($repo, $request_path, $hash);
?>

<div class="container blob">
  <?php if(str_starts_with($mime, 'image/')): ?>
    <img src="data:<?= esc_attr($mime) ?>;base64,<?= base64_encode($blob) ?>">
  <?php elseif($mime == 'application/octet-stream'): ?>
    <p>Cannot render binary data.</p>
  <?php elseif($ext == 'md'): ?>
    <article class="readme">
      <?php
        $parser = new Markdown($repo, $hash, path_parent($request_path));
        echo $parser->text($blob);
      ?>
    </article>
  <?php else: ?>
    <pre class="code"><code><?= htmlspecialchars($blob) ?></code></pre>

    <?php
      // CodeMirror is not a fan of text/x-shellscript.
      $mode = $mime == 'text/x-shellscript' ? 'text/x-sh' : $mime;
    ?>

    <script>
      const codeElement = document.querySelector(".code");
      const containerElement = document.querySelector(".blob");

      CodeMirror(containerElement, {
        value: codeElement.innerText,
        mode:  <?= json_encode($mode) ?>,
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
