<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Form\Transformer\HTMLPurifierTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * CKEditor type.
 */
class HtmleditorType extends AbstractType
{
    protected $transformers;

    public function __construct()
    {
        $this->addTransformer(new HTMLPurifierTransformer(), 'html_purifier');
    }

    public function addTransformer(DataTransformerInterface $transformer, $alias)
    {
        if (isset($this->transformers[$alias])) {
            throw new \Exception('Transformer alias must be unique.');
        }
        $this->transformers[$alias] = $transformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        foreach ($options['transformers'] as $transformer_alias) {
            if (isset($this->transformers[$transformer_alias])) {
                $builder->addViewTransformer($this->transformers[$transformer_alias]);
            } else {
                throw new \Exception(sprintf("'%s' is not a valid transformer.", $transformer_alias));
            }
        }

        $builder
            ->setAttribute('toolbar', $options['toolbar'])
            ->setAttribute('toolbar_groups', $options['toolbar_groups'])
            ->setAttribute('ui_color', $options['ui_color'] ? '#' . ltrim($options['ui_color'], '#') : null)
            ->setAttribute('startup_outline_blocks', $options['startup_outline_blocks'])
            ->setAttribute('width', $options['width'])
            ->setAttribute('height', $options['height'])
            ->setAttribute('force_paste_as_plaintext', $options['force_paste_as_plaintext'])
            ->setAttribute('language', $options['language'])
            ->setAttribute('filebrowser_browse_url', $options['filebrowser_browse_url'])
            ->setAttribute('filebrowser_upload_url', $options['filebrowser_upload_url'])
            ->setAttribute('filebrowser_image_browse_url', $options['filebrowser_image_browse_url'])
            ->setAttribute('filebrowser_image_upload_url', $options['filebrowser_image_upload_url'])
            ->setAttribute('filebrowser_flash_browse_url', $options['filebrowser_flash_browse_url'])
            ->setAttribute('filebrowser_flash_upload_url', $options['filebrowser_flash_upload_url'])
            ->setAttribute('skin', $options['skin'])
            ->setAttribute('format_tags', $options['format_tags'])
            ->setAttribute('base_path', $options['base_path'])
            ->setAttribute('base_href', $options['base_href'])
            ->setAttribute('body_class', $options['body_class'])
            ->setAttribute('contents_css', $options['contents_css'])
            ->setAttribute('basic_entities', $options['basic_entities'])
            ->setAttribute('entities', $options['entities'])
            ->setAttribute('entities_latin', $options['entities_latin'])
            ->setAttribute('startup_mode', $options['startup_mode'])
            ->setAttribute('on', $options['on'])
            ->setAttribute('templates_files', $options['templates_files'])
        ;
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if (!is_array($options['toolbar_groups']) || count($options['toolbar_groups']) < 1) {
            throw new \Exception('You must supply at least 1 toolbar group.');
        }

        $toolbar_groups_keys = array_keys($options['toolbar_groups']);

        $toolbar = [];

        foreach ($options['toolbar'] as $toolbar_id) {
            if ("/" == $toolbar_id) {
                $toolbar[] = $toolbar_id;
            } else {
                if (!in_array($toolbar_id, $toolbar_groups_keys, true)) {
                    throw new \Exception('The toolbar "' . $toolbar_id . '" does not exist. Known options are ' . implode(", ", $toolbar_groups_keys));
                }

                $toolbar[] = [
                    'name' => $toolbar_id,
                    'items' => $options['toolbar_groups'][$toolbar_id],
                ];
            }
        }

        $view->vars['toolbar'] = $toolbar;
        $view->vars['startup_outline_blocks'] = $options['startup_outline_blocks'];
        $view->vars['ui_color'] = $options['ui_color'];
        $view->vars['width'] = $options['width'];
        $view->vars['height'] = $options['height'];
        $view->vars['force_paste_as_plaintext'] = $options['force_paste_as_plaintext'];
        $view->vars['language'] = $options['language'];
        $view->vars['filebrowser_browse_url'] = $options['filebrowser_browse_url'];
        $view->vars['filebrowser_upload_url'] = $options['filebrowser_upload_url'];
        $view->vars['filebrowser_image_browse_url'] = $options['filebrowser_image_browse_url'];
        $view->vars['filebrowser_image_upload_url'] = $options['filebrowser_image_upload_url'];
        $view->vars['filebrowser_flash_browse_url'] = $options['filebrowser_flash_browse_url'];
        $view->vars['filebrowser_flash_upload_url'] = $options['filebrowser_flash_upload_url'];
        $view->vars['skin'] = $options['skin'];
        $view->vars['format_tags'] = $options['format_tags'];
        $view->vars['base_path'] = $options['base_path'];
        $view->vars['base_href'] = $options['base_href'];
        $view->vars['body_class'] = $options['body_class'];
        $view->vars['contents_css'] = $options['contents_css'];
        $view->vars['basic_entities'] = $options['basic_entities'];
        $view->vars['entities'] = $options['entities'];
        $view->vars['entities_latin'] = $options['entities_latin'];
        $view->vars['startup_mode'] = $options['startup_mode'];
        $view->vars['external_plugins'] = $options['external_plugins'];
        $view->vars['custom_config'] = $options['custom_config'];
        $view->vars['on'] = $options['on'];
        $view->vars['templates_files'] = $options['templates_files'];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'transformers' => [],
            'toolbar' => ['document', 'clipboard', 'editing', '/', 'basicstyles', 'paragraph', 'links', '/', 'insert', 'styles', 'tools'],
            'toolbar_groups' => [
                'document' => ['Source', '-', 'Save', '-', 'Templates'],
                'clipboard' => ['Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo'],
                'editing' => ['Find', 'Replace', '-', 'SelectAll'],
                'basicstyles' => ['Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'RemoveFormat'],
                'paragraph' => ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock'],
                'links' => ['Link', 'Unlink', 'Anchor'],
                'insert' => ['Image', 'Flash', 'Table', 'HorizontalRule'],
                'styles' => ['Styles', 'Format'],
                'tools' => ['Maximize', 'ShowBlocks'],
            ],
            'startup_outline_blocks' => false,
            'ui_color' => '#000000',
            'width' => '100%',
            'height' => 300,
            'force_paste_as_plaintext' => null,
            'language' => null,
            'filebrowser_browse_url' => null,
            'filebrowser_upload_url' => null,
            'filebrowser_image_browse_url' => null,
            'filebrowser_image_upload_url' => null,
            'filebrowser_flash_browse_url' => null,
            'filebrowser_flash_upload_url' => null,
            'skin' => null,
            'format_tags' => [],
            'base_path' => 'assets/common/vendors/ckeditor/',
            'base_href' => null,
            'body_class' => null,
            'contents_css' => null,
            'basic_entities' => null,
            'entities' => null,
            'entities_latin' => null,
            'startup_mode' => null,
            'external_plugins' => [],
            'custom_config' => null,
            'on' => null,
            'templates_files' => null,
        ]);

        $resolver->setAllowedValues('startup_mode', ['wysiwyg', 'source', null]);

        foreach ([
            'transformers' => 'array',
            'toolbar' => 'array',
            'toolbar_groups' => 'array',
            'format_tags' => 'array',
            'external_plugins' => 'array',
            'force_paste_as_plaintext' => ['bool', 'null'],
            'basic_entities' => ['bool', 'null'],
            'startup_outline_blocks' => ['bool', 'null'],
        ] as $optionName => $optionTypes) {
            $resolver->setAllowedTypes($optionName, $optionTypes);
        }
    }

    public function getParent()
    {
        return TextareaType::class;
    }

    public function getBlockPrefix()
    {
        return 'htmleditor';
    }
}
