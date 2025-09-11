<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/diff2html/bundles/css/diff2html.min.css" />
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/diff2html/bundles/js/diff2html-ui.min.js"></script>

<link
  rel="stylesheet"
  href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/styles/github.min.css"
  media="screen and (prefers-color-scheme: light)"
/>

<link
  rel="stylesheet"
  href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/styles/github-dark.min.css"
  media="screen and (prefers-color-scheme: dark)"
/>

<div class="container commit-details">
  <?php $commit = \core\getCommit($repo, $hash) ?>

  <hgroup>
    <h2><?= htmlspecialchars($commit['subject']) ?></h2>

    <p>
      Commited on <time><?= \dates\humanReadable($commit['datetime']) ?></time>
      by <a href="mailto:<?= $commit['email'] ?>"><?= htmlspecialchars($commit['author']) ?></a>.
    </p>
  </hgroup>

  <?php
    $object = $repo->getCommit($hash);
    $message = $object->getBody();

    if($message) echo '<pre class="message">' . htmlspecialchars($message) . '</pre>';
  ?>

  <pre class="diff"><code><?= htmlspecialchars($commit['diff']) ?></code></pre>
</div>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const originElement = document.querySelector(".diff");
    const targetElement = document.createElement("div");
    const containerElement = document.querySelector(".commit-details");

    // Fix to prevent overflow
    targetElement.style.maxWidth = "100%";

    // Insert target div for for rendering diff2html
    const code = originElement.innerText;
    containerElement.insertBefore(targetElement, originElement);

    // Remove original element from page
    originElement.remove();
    delete originElement;

    const diff = new Diff2HtmlUI(targetElement, code, {
      synchronisedScroll: true,
      highlight: true,
      drawFileList: false,
      fileContentToggle: false,
      stickyFileHeaders: false,
      outputFormat: 'side-by-side',
      colorScheme: 'auto'
    });

    diff.draw();
  });
</script>