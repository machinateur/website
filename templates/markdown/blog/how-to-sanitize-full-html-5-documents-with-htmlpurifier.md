# How to sanitize full HTML 5 documents with HTMLPurifier

When it comes to sanitization of HTML content, HTMLPurifier ([`ezyang/htmlpurifier`](https://github.com/ezyang/htmlpurifier))
 is a popular library. Sanitization is commonly used in content management systems
 and really all kinds of software that deals with web content.

Not long ago I had to find out the hard way, that it has its drawbacks, in terms of usability.
That's because it does not come with HTML5 support.

That said, let me just mention that this article is quite shopware 6 specific.

## The use-case

For better understanding, here is the example case:

In shopware 6, the library is used to sanitize HTML content from the administration, among other uses, for mail templates.
And when it comes to e-mails, there are scenarios, when you would want to send a complete HTML 5 document,
 along with its `<html/>`, `<head/>` and `<body/>` elements.

As I found, the purifier does not know about these tags in any way. Same goes for the `<style/>` or `<meta/>`.
So if they're used in an e-mail template, they would be purged from the content, along with their content.
 But the library will also throw errors when there are unsupported tags in the markup in some cases.

## Adding support for HTML5 elements

To my luck, there is a configuration option built-in to shopware, which allows to extend the options for the purifier.
 And this is what it looks like in the default yaml config:

```yaml
shopware:
 
    # ...

    html_sanitizer:
        sets:
            - name: HTML5
              tags: ["article", "aside", "audio", "bdi", "canvas", "datalist", "details", "dialog", "embed", "figcaption", "figure", "footer", "header", "main", "mark", "meter", "nav", "progress", "rp", "rt", "ruby", "section", "summary", "time", "wbr", "output", "canvas", "svg", "track", "video", "source", "input"]
              attributes: ["controls", "open", "min", "max", "datetime", "for", "type", "kind", "srclang", "label", "value", "placeholder", "autoplay", "loop", "muted", "preload", "low", "high", "optimum", "default", "poster", "media", "maxlength", "minlength", "pattern", "required", "autocomplete", "autofocus", "disabled", "readonly", "multiple", "formaction", "formenctype", "formmethod", "formnovalidate", "formtarget", "list", "step", "checked", "accept"]
            - name: basic
              tags: ["a", "abbr", "acronym", "address", "b", "bdo", "big", "blockquote", "br", "caption", "center", "cite", "code", "col", "colgroup", "dd", "del", "dfn", "dir", "div", "dl", "dt", "em", "font", "h1", "h2", "h3", "h4", "h5", "h6", "hr", "i", "ins", "kbd", "li", "menu", "ol", "p", "pre", "q", "s", "samp", "small", "span", "strike", "strong", "sub", "sup", "table", "tbody", "td", "tfoot", "th", "thead", "tr", "tt", "u", "ul", "var", "img"]
              attributes: ["align", "bgcolor", "border", "cellpadding", "cellspacing", "cite", "class", "clear", "color", "colspan", "dir", "face", "frame", "height", "href", "id", "lang", "name", "noshade", "nowrap", "rel", "rev", "rowspan", "scope", "size", "span", "start", "style", "summary", "title", "type", "valign", "value", "width", "target", "src", "alt"]
              options:
                  - key: Attr.AllowedFrameTargets
                    values: ['_blank', '_self', '_parent', '_top']
                  - key: Attr.AllowedRel
                    values: ['nofollow', 'print']
                  - key: Attr.EnableID
                    value: true
            - name: media
              tags: ["img"]
              attributes: ["src", "alt"]
            - name: script
              tags: ["script"]
              options:
                  - key: HTML.Trusted
                    value: true
            - name: tidy
              options:
                  - key: Output.TidyFormat
                    value: true
            - name: bootstrap
              tags: ["a", "span"]
              attributes: ["role", "aria-label", "aria-labelledly", "aria-current", "aria-expanded", "aria-controls", "aria-hidden", "aria-describedby", "tabindex", "aria-modal", "data-bs-toggle", "data-bs-target", "data-bs-dismiss", "data-bs-slide", "data-bs-slide-to", "data-bs-parent", "data-bs-config", "data-bs-content", "data-bs-spy"]
              custom_attributes:
                  - tags: ["a", "span"]
                    attributes: ["href", "role", "aria-label", "aria-labelledly", "aria-current", "aria-expanded", "aria-controls", "aria-hidden", "aria-describedby", "tabindex", "aria-modal", "data-bs-toggle", "data-bs-target", "data-bs-dismiss", "data-bs-slide", "data-bs-slide-to", "data-bs-parent", "data-bs-config", "data-bs-content", "data-bs-spy"]
            - name: snippet
              tags: ["a"]
              attributes: ["data-url", "data-ajax-modal", "data-prev-url"]
              custom_attributes:
                  - tags: ["a"]
                    attributes: ["data-url", "data-ajax-modal", "data-prev-url"]

        fields:
            - name: product_translation.description
              sets: ["basic", "media", "HTML5"]
            - name: app_cms_block.template
              sets: ["basic", "media", "tidy", "HTML5"]
            - name: snippet.value
              sets: ["basic", "media", "bootstrap", "snippet", "HTML5"]
```

([source](https://github.com/shopware/shopware/blob/67568972620263265559eeb2014caca14f700db7/src/Core/Framework/Resources/config/packages/shopware.yaml#L295))

That lead me to the following lines of code, where the configuration is applied.

```php
        // some processing of the yaml structure

        $config->set('HTML.AllowedElements', $allowedElements);
        $config->set('HTML.AllowedAttributes', $allowedAttributes);

        $definition = $config->getHTMLDefinition(true);

        if ($definition === null) {
            return $config;
        }

        $this->addHTML5Tags($definition);

        foreach ($customAttributes as $tag => $attributes) {
            foreach ($attributes as $attribute) {
                $definition->addAttribute($tag, $attribute, 'Text');
            }
        }
```

([source](https://github.com/shopware/shopware/blob/67568972620263265559eeb2014caca14f700db7/src/Core/Framework/Util/HtmlSanitizer.php#L92))

It is then used to construct the `\HTMLPurifier` instance.

If you're interested in what configurations exactly are necessary to support HTML5
<a data-bs-toggle="collapse" href="#collapseHtml5Configuration" aria-expanded="false" aria-controls="collapseHtml5Configuration">click here</a>
or [have a look at source code](https://github.com/shopware/shopware/blob/67568972620263265559eeb2014caca14f700db7/src/Core/Framework/Util/HtmlSanitizer.php#L159)
in their repository.

<div class="collapse" id="collapseHtml5Configuration">
<pre><code class="language-php">
private function addHTML5Tags(\HTMLPurifier_HTMLDefinition $definition): \HTMLPurifier_HTMLDefinition
{
    $definition->addElement('section', 'Block', 'Flow', 'Common');
    $definition->addElement('nav', 'Block', 'Flow', 'Common');
    $definition->addElement('article', 'Block', 'Flow', 'Common');
    $definition->addElement('aside', 'Block', 'Flow', 'Common');
    $definition->addElement('header', 'Block', 'Flow', 'Common');
    $definition->addElement('footer', 'Block', 'Flow', 'Common');
    $definition->addElement('canvas', 'Block', 'Flow', 'Common', [
        'width' => 'Length',
        'height' => 'Length',
    ]);
    $definition->addElement('bdi', 'Block', 'Flow', 'Common');
    $definition->addElement('audio', 'Block', 'Flow', 'Common', [
        'src' => 'URI',
        'preload' => 'Enum#auto,metadata,none',
        'autoplay' => new \HTMLPurifier_AttrDef_HTML_Bool(true),
        'loop' => new \HTMLPurifier_AttrDef_HTML_Bool(true),
        'muted' => new \HTMLPurifier_AttrDef_HTML_Bool(true),
        'controls' => new \HTMLPurifier_AttrDef_HTML_Bool(true),
    ]);
    $definition->addElement('datalist', 'Block', 'Flow', 'Common', [
        'id' => 'ID',
    ]);
    $definition->addElement('dialog', 'Block', 'Flow', 'Common', [
        'open' => new \HTMLPurifier_AttrDef_HTML_Bool(true),
    ]);
    $definition->addElement('embed', 'Block', 'Flow', 'Common', [
        'src' => 'URI',
        'type' => 'Text',
        'width' => 'Length',
        'height' => 'Length',
    ]);
    $definition->addElement('main', 'Block', 'Flow', 'Common');
    $definition->addElement('menu', 'Block', 'Flow', 'Common');
    $definition->addElement('meter', 'Block', 'Flow', 'Common', [
        'form' => 'ID',
        'value' => 'Text',
        'min' => 'Length',
        'max' => 'Length',
        'low' => 'Text',
        'high' => 'Text',
        'optimum' => 'Text',
    ]);
    $definition->addElement('progress', 'Block', 'Flow', 'Common', [
        'value' => 'Number',
        'max' => 'Number',
    ]);
    $definition->addElement('rp', 'Block', 'Flow', 'Common');
    $definition->addElement('rt', 'Block', 'Flow', 'Common');
    $definition->addElement('ruby', 'Block', 'Flow', 'Common');
    $definition->addElement('summary', 'Block', 'Flow', 'Common');
    $definition->addElement('time', 'Block', 'Flow', 'Common', [
        'datetime' => 'Text',
    ]);
    $definition->addElement('output', 'Block', 'Flow', 'Common', [
        'for' => 'ID',
        'form' => 'ID',
        'name' => 'CDATA',
    ]);
    $definition->addElement('svg', 'Block', 'Flow', 'Common', [
        'width' => 'Length',
        'height' => 'Length',
    ]);
    $definition->addElement('track', 'Block', 'Flow', 'Common', [
        'default' => new \HTMLPurifier_AttrDef_HTML_Bool(true),
        'kind' => 'Enum#subtitles,captions,descriptions,chapters,metadata',
        'label' => 'Text',
        'src' => 'URI',
        'srclang' => 'LanguageCode',
    ]);
    $definition->addElement(
        'details',
        'Block',
        'Flow',
        'Common',
        [
            'open' => new \HTMLPurifier_AttrDef_HTML_Bool(true),
        ]
    );

    $definition->addElement('figure', 'Block', 'Optional: (figcaption, Flow) | (Flow, figcaption) | Flow', 'Common');
    $definition->addElement('figcaption', 'Inline', 'Flow', 'Common');
    $definition->addElement('video', 'Block', 'Optional: (source, Flow) | (Flow, source) | Flow', 'Common', [
        'src' => 'URI',
        'width' => 'Length',
        'height' => 'Length',
        'poster' => 'URI',
        'preload' => 'Enum#auto,metadata,none',
        'controls' => new \HTMLPurifier_AttrDef_HTML_Bool(true),
        'autoplay' => new \HTMLPurifier_AttrDef_HTML_Bool(true),
        'loop' => new \HTMLPurifier_AttrDef_HTML_Bool(true),
        'muted' => new \HTMLPurifier_AttrDef_HTML_Bool(true),
    ]);
    $definition->addElement('source', 'Block', 'Flow', 'Common', [
        'src' => 'URI',
        'type' => 'Text',
        'media' => 'Text',
        'sizes' => 'Text',
        'srcset' => 'Text',
        'crossorigin' => 'Enum#anonymous,use-credentials',
    ]);

    $definition->addElement('mark', 'Inline', 'Inline', 'Common');
    $definition->addElement('wbr', 'Inline', 'Empty', 'Core');

    // Add new HTML5 input types
    $definition->addElement(
        'input',
        'Form',
        'Empty',
        'Common',
        [
            'accept' => 'Text',
            'alt' => 'Text',
            'autocomplete' => 'Enum#on,off',
            'autofocus' => new \HTMLPurifier_AttrDef_HTML_Bool(true),
            'checked' => new \HTMLPurifier_AttrDef_HTML_Bool(true),
            'dirname' => 'Text',
            'disabled' => new \HTMLPurifier_AttrDef_HTML_Bool(true),
            'form' => 'ID',
            'formaction' => 'URI',
            'formenctype' => 'Enum#application/x-www-form-urlencoded,multipart/form-data,text/plain',
            'formmethod' => 'Enum#get,post',
            'formnovalidate' => new \HTMLPurifier_AttrDef_HTML_Bool(true),
            'formtarget' => 'Enum#_blank,_self,_parent,_top',
            'height' => 'Length',
            'list' => 'ID',
            'max' => 'Text',
            'maxlength' => 'Number',
            'min' => 'Text',
            'minlength' => 'Number',
            'multiple' => new \HTMLPurifier_AttrDef_HTML_Bool(true),
            'name' => 'CDATA',
            'pattern' => 'Text',
            'placeholder' => 'Text',
            'readonly' => new \HTMLPurifier_AttrDef_HTML_Bool(true),
            'required' => new \HTMLPurifier_AttrDef_HTML_Bool(true),
            'size' => 'Number',
            'src' => 'URI',
            'step' => 'Text',
            'type' => 'Enum#text,password,checkbox,radio,submit,reset,file,hidden,image,button,date,time,datetime-local,week,month,number,email,url,search,tel,color,range',
            'value' => 'Text',
            'width' => 'Length',
        ]
    );

    return $definition;
}
</code></pre>
</div>

## Support for full documents

Following [the documentation](http://htmlpurifier.org/docs/enduser-customize.html) on the matter of adding custom elements
 available on the website, I came up with the following approach:

```php
        $definition = $config->getHTMLDefinition(true);

        \assert($definition instanceof \HTMLPurifier_HTMLDefinition);

        // Add dummy document root element
        $definition->addElement('__document_root', false,
            'required: html',
            null,
        );
        // Add <html/> element (document root)
        $definition->addElement('html', 'Document',
            // The head/body are required in the <html/> tag
            'required: head | body',
            null,
            [
                'lang' => 'Text',
                // 'charset' => 'Charsets',
            ],
        );
        // Add <head/> element
        $definition->addElement('head', false,
            // These three elements are allowed as children, but not required
            'optional: meta | link | style',
            null,
        );
        // Add <body/> element
        $definition->addElement('body', false,
            'Flow',
            // Add common attributes to the <body/> tag
            'Common',
        );

        // Add <meta/> element
        $definition->addElement('meta', false,
            'Empty',
            null,
            [
                'name'    => 'NMTOKENS',
                'content' => 'Text',
            ],
        );
        // Add <link/> element
        $definition->addElement('link', false,
            'Empty',
            null,
            [
                // The "href" is a URL
                'href' => 'URI',
                // Force "rel" to stylesheet
                'rel' => 'Enum#stylesheet',
                // Force the "type" value to text/css
                'type' => 'Enum#text/css',
            ],
        );
        // Add <style/> element
        $definition->addElement('style', 'Inline',
            // Not Empty to not allow to auto-close the <style/> tag
            'required: #PCDATA',
            null,
            [
                // Force the "type" attribute value to text/css
                'type' => 'Enum#text/css',
            ],
        );
```

As you can see, I've added a `__document_root` element, which is a dummy used for the parent of `html`,
 which is the required child of that element. The `html` in turn has `head` and `body` as required children (one of 'em).
The `body` then again allows all `Flow` content and so on. It's safe, since it is not allowed in any of the other elements.
 It will only be used as `HTML.Parent` option value for the lexer to understand our bidding.

In the above example, I also defined `style`, `link` and `meta` tags, which should suffice for most use-cases.

What I've not shown here, is how these settings are injected into the configuration. It's impossible to use inheritance,
 which leaves reflection and closure scopes. There are lots of examples on the internet on how that can be done.

At last, the `\Shopware\Core\Framework\Util\HtmlSanitizer` should be decorated in the service definition like this:

```yaml
services:
    # ...

    Machinateur\Shopware\Core\Framework\Util\HtmlSanitizer:
        decorates: 'Shopware\Core\Framework\Util\HtmlSanitizer'
```

See the [symfony docs](https://symfony.com/doc/current/service_container/service_decoration.html)
 for more information on how to decorate services.

But this is only one part of the puzzle...

## Shopware configuration for full documents

Utilizing the YAML configuration, we can now allow a new _set_ of elements.
And this is a crucial part for this extension to work, since the options are very specific for different use-cases.
You can read about the options at [the official documentation](http://htmlpurifier.org/live/configdoc/plain.html).

See the configuration below:

```yaml
shopware:

    # ...

    html_sanitizer:
        sets:
            # Allow wrapping document elements in mail header/footer content
            - name: full_document
              tags: ['html', 'head', 'body', 'meta', 'link']
              attributes: ['lang', 'name', 'content', 'rel', 'href', 'type']
              options:
                  # All options are documented at http://htmlpurifier.org/live/configdoc/plain.html
                  - key: CSS.AllowImportant
                    value: true
                  # We are more permissive regarding the allowed "rel" values, to properly support links in head
                  - key: Attr.AllowedRel
                    values: ['nofollow', 'print', 'stylesheet']
                  # Avoid removing the outer document, to keep that content intact
                  - key: Core.ConvertDocumentToFragment
                    value: false
                  # Set up the dummy element as top-most parent element
                  - key: HTML.Parent
                    value: '__document_root'
                  # We do not wish to loose any unclosed tags, as header/footer are separated
                  - key: Core.EscapeInvalidTags
                    value: true
                  # Set the lexer implementation explicitly, as the wrong lexer might break full documents
                  - key: Core.LexerImpl
                    value: 'DirectLex'
            # Allow inline style tags in mail body (template) content
            - name: style
              tags: ['style']
              attributes: ['type']
              options:
                  - key: CSS.Trusted
                    value: true
        
        fields:
            # Set up special rule for mail template
            - name: mail_template_translation.contentHtml
              sets: ['basic', 'style', 'HTML5', 'full_document']
```

### Setting this up for e-mail header/footer

So, as you might imagine, it could be a bad idea to use this with header and footer on mails.
 That's, to state the obvious, because the template will be enclosed in header and footer template.

So you should take measures to ensure there is no possibility to set these templates on sales-channels
 using full document e-mails. Everything else on your own risk.

It's a bit tricky to make full documents work for the header-footer use-case. You'd want the mail template itself to
 only allow basic HTML content, and the header-footer set to full documents.

The configuration or that would be this:

```yaml
shopware:

    # ...

    html_sanitizer:
        fields:
            # Set up special rule for mail header/footer template
            - name: mail_header_footer_translation.contentHtml
              sets: ['basic', 'style'] # maybe also HTML5
```

That field does not exist, but I'll come to that shortly.

Apart from that, there's a not insignificant part involved in sticking the both parts of the document template together.

To do so, first the validation has to be switched off for the definition. SUch thing can be achieved by changing
 the `class` of the service definition for the _entity definition_ in the service container. Override the fields by just
 re-adding the exiting ones to the defined fields from `parent`.
The new class should inherit all other parts of the definition. The new fields for header/footer HTML should also get
 the `AllowHtml` flag, with parameter `false`.
That way, no validation is done through the sanitizer (which gets called by the field serializer).

```php
<?php

declare(strict_types=1);

namespace Machinateur\Shopware\Core\Content\MailTemplate\Aggregate;

use Shopware\Core\Content\MailTemplate\Aggregate\MailHeaderFooterTranslation\MailHeaderFooterTranslationDefinition as MailHeaderFooterTranslationDefinitionBase;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\AllowHtml;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class MailHeaderFooterTranslationDefinition extends MailHeaderFooterTranslationDefinitionBase
{
    public function defineFields(): FieldCollection
    {
        $def = parent::defineFields();
        $def->add(
            (new LongTextField('header_html', 'headerHtml'))
                ->addFlags(new AllowHtml(false))
        );
        $def->add(
            (new LongTextField('footer_html', 'footerHtml'))
                ->addFlags(new AllowHtml(false))
        );

        return $def;
    }
}
```

With the following YAML service definition:

```yaml
services:
    Machinateur\Shopware\:
        # the path is relative to `src/Resources/config/services.yaml`
        resource: '../../'
        exclude:
            - '../../Core/Content/MailTemplate/Aggregate/MailHeaderFooterTranslation/MailHeaderFooterTranslationDefinition.php'
            # ...

    Shopware\Core\Content\MailTemplate\Aggregate\MailHeaderFooterTranslation\MailHeaderFooterTranslationDefinition:
        class: 'Machinateur\Shopware\Core\Content\MailTemplate\Aggregate\MailHeaderFooterTranslationDefinition'
```

The definition class is used instead of the original one from shopware,
 while the same new class is excluded from the resource definition at the top.
That is necessary to avoid issues with duplicate entity names, as the new definition would otherwise be discovered,
 tagged and registered (autowiring process). This is a general way to overcome the strict entity extension rules,
 put up by shopware.
Doing this should be the last resort, when the usual entity extension capabilities are not sufficient, like in this case.

Now we have to account for the validation ourselves, whenever the entity data of the `MailHeaderFooterTranslationEntity`
 is updated. This in turn can be achieved using the event system of shopware.
The docs explain how it's possible to access and change data.

```php
<?php

declare(strict_types=1);

namespace Machinateur\Shopware\Core\Content\MailTemplate\Aggregate;

// ...

class MailHeaderFooterWriteSubscriber implements EventSubscriberInterface
{
    public function __construct(
        protected readonly HtmlSanitizer $sanitizer,
        protected readonly EntityRepository $mailHeaderFooterTranslationRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PreWriteValidationEvent::class => 'beforeWrite',
        ];
    }

    public function beforeWrite(PreWriteValidationEvent $event): void
    {
        foreach ($event->getCommands() as $command) {
            $definition = $command->getDefinition();

            // Only apply to the specific header/footer entity.
            if ('mail_header_footer_translation' !== $definition->getEntityName()) {
                continue;
            }

            \assert($definition instanceof MailHeaderFooterTranslationDefinition);

            // Skip if no relevant fields is changed.
            if (!$command->hasField('header_html')
                && !$command->hasField('footer_html')) {
                continue;
            }

            // Load the current entity data.
            $entity = $this->getExistingEntity($command, $event->getContext());
            if (null === $entity) {
                continue;
            }

            // Random divider UUID.
            $divider = Uuid::randomHex();

            $payload = $command->getPayload();
            $payload['header_html'] ??= $entity->get('headerHtml');
            $payload['footer_html'] ??= $entity->get('footerHtml');

            $text = sprintf('%s%s%s',
                $payload['header_html'], $divider, $payload['footer_html']
            );
            $text = $this->sanitizer->sanitize($text, field: 'mail_header_footer_translation.contentHtml');
            // This is where the dummy name comes in again.  ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

            [$headerHtml, $footerHtml] = explode($divider, $text, 2);

            $command->addPayload('header_html', $headerHtml);
            $command->addPayload('footer_html', $footerHtml);
        }
    }

    // ...
}
```

The key element is, that our custom logic should concatenate both parts, with a unique separator in between,
 then sanitize the contents, which should make a full document, when valid.
That separator should be as random and unique as possible to avoid wrong splitting,
 when the sanitized text is taken apart again. A good separator could be a random `Uuid` in HEX format.

The `HtmlSanitizer::sanitize()` method allows to supply the `$field`, which should be a field we've previously set up
 in the YAML config. In the example above, that's `mail_header_footer_translation.contentHtml`, a non-existent field on
 that entity, but existence does not really matter here. We could also abuse the specific header/footer fields, but since
 both will be parsed as one document, it makes absolutely no difference, except more config boilerplate would be required.

When all that is done and in place, we should be able to handle full documents in the e-mail template generation, which
 on top of that can contain twig directives. Let me know what you think.

Happy coding!
