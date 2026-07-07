<?php

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

class GitImageRenderer implements NodeRendererInterface {
  public function __construct(private $repo, private $hash, private string $base) {}

  public function render(Node $node, ChildNodeRendererInterface $childRenderer): string {
    $src = $node->getUrl();

    if (!is_url($src)) {
      $path = trim(path_join($this->base, $src), '/');
      try {
        $blob = \core\getBlob($this->repo, $path, $this->hash);
        $mime = \core\detectMimeType($this->repo, $path, $this->hash);
        $src = "data:{$mime};base64," . base64_encode($blob);
      } catch (\Throwable) {}
    }

    $alt = strip_tags($childRenderer->renderNodes($node->children()));
    $attrs = 'src="' . esc_attr($src) . '" alt="' . esc_attr($alt) . '"';

    if (($title = $node->getTitle()) !== null) {
      $attrs .= ' title="' . esc_attr($title) . '"';
    }

    return "<img {$attrs}>";
  }
}

class GitLinkRenderer implements NodeRendererInterface {
  public function __construct(private string $base) {}

  public function render(Node $node, ChildNodeRendererInterface $childRenderer): string {
    $href = $node->getUrl();

    if (!is_url($href) && !str_starts_with($href, '/') && !str_starts_with($href, '#') && !str_contains($href, '://')) {
      $href = rtrim($this->base, '/') . '/' . $href;
    }

    $text = $childRenderer->renderNodes($node->children());
    $attrs = 'href="' . esc_attr($href) . '"';

    if (($title = $node->getTitle()) !== null) {
      $attrs .= ' title="' . esc_attr($title) . '"';
    }

    return '<a ' . $attrs . '>' . $text . '</a>';
  }
}

class Markdown {
  private MarkdownConverter $converter;

  public function __construct($repo, $hash, string $base) {
    $env = new Environment(['html_input' => 'escape', 'allow_unsafe_links' => false]);
    $env->addExtension(new GithubFlavoredMarkdownExtension());
    $env->addRenderer(Image::class, new GitImageRenderer($repo, $hash, $base));

    $repo_path = ltrim(substr($repo->getRepositoryPath(), strlen(SCAN_PATH)), '/');
    $link_base = GITZ_URL . '/~' . $repo_path . '/tree/' . $hash . ($base !== '' ? '/' . $base : '');
    $env->addRenderer(Link::class, new GitLinkRenderer($link_base));

    $this->converter = new MarkdownConverter($env);
  }

  public function text(string $content): string {
    return $this->converter->convert($content)->getContent();
  }
}
