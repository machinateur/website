# Twig Block Validator for Shopware 6

Ok, so this is kind of also a small showcase / dev-log of Twig Block Validator.
It does what it says in the name. But completely in PHP.

You can [skip here](), to just see how to install and use the validator in Shopware a project.

## Pretext

The concept itself is in no way new, and the PhpStorm [Shopware 6 Toolbox]() plugin was certainly the inspiration.

```twig
{% sw_extends "@Storefront/storefront/base.html.twig" %}

{# shopware-block: c1954b12f0c43a0244c3d781256b6aa676b5699a9700c10983a468a68a0e5eb1@v6.6.6.0 #}
{% block base_body_skip_to_content %}
    My overwrite content
{% endblock %}
```

But I wanted to go one step further: Validating Twig template syntax, as well as block inheritance, automatically.
The version comments directly on the line before the block opening tag, as shown above,
are used to track the changes between version in their respective top-most parent (what I call the *origin block*).

I thought about how this could serve in CI pipelines, to make sure it's detected whenever my template dependencies change.

Where this is particularly true, to my experience, is in Shopware projects or plugins
that heavily extend and use the Storefront Twig templates and features.

Just know, that the project at the time of writing this is still in beta,
and I'm not yet sure how well it will handle large code bases of templates with many dependencies.
I in no way claim it is feature complete nor foolproof. Feedback and contributions are very much welcome and appreciated!

---

> This was one of those ideas, where you start out fairly simple, and it turns out to be more than you bargained for.

The to-be basic tool turned out more complicated under the hood.

## Comments and the Twig Lexer

The goal for the prototype was to get something running that could simply read comments from Twig templates,
and record those in logical relation to the related block.
After all, this is what it all comes down to. Without that capability, it wouldn't be possible to process any metadata.

There is only one thing about comments in Twig's lexer... **They're skipped**. Here the excerpt:

```php
    private function lexComment(): void
    {
        if (!preg_match($this->regexes['lex_comment'], $this->code, $match, \PREG_OFFSET_CAPTURE, $this->cursor)) {
            throw new SyntaxError('Unclosed comment.', $this->lineno, $this->source);
        }

        $this->moveCursor(substr($this->code, $this->cursor, $match[0][1] - $this->cursor).$match[0][0]);
    }
```

From [`\Twig\Lexer`](https://github.com/twigphp/Twig/blob/3.x/src/Lexer.php).

To get around that limitation, it's possible to write a Twig lexer. So I did.

What made it slightly more complicated was, that there is only one method exposed by that base class (i.e. `tokenize()`),
and there is no interface to implement. In the end there was a way, but it wasn't pretty,
involving exceptions and voodoo I don't want to talk about.

That spike resulted in the [`machinateur/twig-comment-lexer`](https://github.com/machinateur/twig-comment-lexer) library,
for future re-use. It makes it quite easy to collect and parse comments now, using just a node visitor. Yes, this is an ad.

## Tracking blocks and comments

Now that we got that out of the way, a system was needed to collect all blocks and their version annotation, if present.

For relative simplicity, I chose to just collect all the blocks and comments there are, and figure it out later.

It's not easily possible to parse blocks from a compiled PHP template,
therefor the template code has to be tokenized, parsed and then compiled in order for the information to be collected.

While it is certainly possible to collect and store context information from templates in their compiled code,
this is not an option here, since it would not change the requirement for the source to be processed at least once.
For accuracy, it wouldn't be safe to rely on the pre-compiled form regardless.

The answer to the question of how to associate blocks and comments to each other was actually not that difficult to find.
The mechanism kind of revealed itself in the data structure, as a single object receives calls from the node visitor,
which makes use of comments exposed by the comment lexer.

```php
        if ($node instanceof ModuleNode) {
            $this->collection->setTemplate($node->getTemplateName());
        }

        if ($node instanceof BlockNode) {
            $name = $node->getAttribute('name');
            // ...

            // The comment has to be located exactly oon the line before the block start.
            //  Call `addComment()` only after entering the *next* node.
            if ($this->previousNode instanceof CommentNode && ! $this->previousNode->exposed
                && $this->previousNode->getTemplateLine() === $node->getTemplateLine() - 1)
            {
                $this->collection->addComment($this->previousNode->text, $this->defaultVersion);
            }
        }
```

More in [`\Machinateur\TwigBlockValidator\Twig\NodeVisitor\BlockNodeVisitor`](https://github.com/machinateur/twig-block-validator/blob/main/src/Twig/NodeVisitor/BlockNodeVisitor.php).

Conveniently, this ensures that only valid comments can be added during processing, since `addComment()` performs validation.
Only if a `{% block %}` directly follows the `{# shopware-block: <hash>@<version> #}` comment,
it will make use of that comment. Any randomly scattered comments and annotations should not be picked up.
The comment's content itself has to strictly match [the predefined pattern](https://regex101.com/r/5JINe6/1).

The `\Machinateur\TwigBlockValidator\Twig\BlockValidatorEnvironemnt` is implemented in a way,
so that it tracks the blocks and comments for each template as it is loaded.
Of course there are some helper methods and other things that didn't fit anywhere else.

Internally it uses the [Symfony cache component](https://symfony.com/doc/current/components/cache.html).

## Navigating between blocks

Finding the *origin block* involves navigating between templates and blocks, then finding their associated annotation (if any).

TODO

- all templates are loaded first, to fill up the cache
- shopware inheritance (sw_extends works)

## Source hash and version

- found based on line number

## Real-world use-case with Shopware plugins

This is arguably the most interesting parts for most, so I saved it for last.

Install the validator in your project like this:

```bash
composer require --dev machinateur/twig-block-validator
```

First, validate all blocks (given the project has a `bin/console` in place):

```bash
# todo: use xargs smartly to get installed plugins/theme namespaces
bin/console twig:block:validate -h
```

I recommend using VCS (i.e. git) past this point, to make sure there is no loss of data, when the templates are annotated in place.
Then, in case there are outdated blocks found, or no version annotations defined yet, simply add those:

```bash
# todo: use xargs smartly to get installed plugins/theme namespaces
bin/console twig:block:annotate -h
```

It's also possible to provide a path where the changed files should be written to, just append it to the command:

```bash
# todo: use xargs smartly to get installed plugins/theme namespaces
bin/console twig:block:annotate -h /var/some/path
```

## Feedback

There's still plenty to improve and probably some bugs to fix or features to add.

- feedback
- contributions
- feature ideas / suggestions
- questions

all via github
