/**
 * YAP Blocks - Gutenberg Editor Integration
 * Handles block preview, edit mode, and field rendering in editor
 */

(function(wp) {
    const { registerBlockType } = wp.blocks;
    const { InspectorControls, BlockControls, useBlockProps, InnerBlocks } = wp.blockEditor;
    const { PanelBody, PanelRow, TextControl, TextareaControl, ToggleControl, SelectControl, 
            RangeControl, ColorPicker, Button, ButtonGroup, Toolbar, ToolbarButton, 
            ToolbarGroup, MediaUpload, MediaUploadCheck } = wp.components;
    const { Fragment, useState, useEffect } = wp.element;
    const { __ } = wp.i18n;
    const { apiFetch } = wp;
    const { useSelect } = wp.data;

    // Register all YAP blocks
    if (typeof yapBlocks !== 'undefined' && yapBlocks.blocks) {
        Object.keys(yapBlocks.blocks).forEach(blockName => {
            const blockConfig = yapBlocks.blocks[blockName];
            
            registerBlockType(blockName, {
                title: blockConfig.title,
                icon: blockConfig.icon,
                category: blockConfig.category,
                keywords: blockConfig.keywords,
                supports: blockConfig.supports,
                attributes: getBlockAttributes(blockConfig),
                
                edit: function(props) {
                    return <YAPBlockEdit {...props} blockConfig={blockConfig} />;
                },
                
                save: function() {
                    // Server-side rendered, return null
                    return null;
                }
            });
        });
    }

    /**
     * Main block edit component
     */
    function YAPBlockEdit({ attributes, setAttributes, clientId, blockConfig }) {
        const [mode, setMode] = useState(attributes.mode || blockConfig.mode || 'preview');
        const [preview, setPreview] = useState('');
        const blockProps = useBlockProps({
            className: `yap-block yap-block-${blockConfig.name} yap-block-mode-${mode}`
        });

        // Fetch preview HTML
        useEffect(() => {
            if (mode === 'preview') {
                fetchBlockPreview();
            }
        }, [attributes, mode]);

        const fetchBlockPreview = async () => {
            try {
                const response = await apiFetch({
                    path: '/yap/v1/block-preview',
                    method: 'POST',
                    data: {
                        block_name: blockConfig.name,
                        attributes: attributes,
                        nonce: yapBlocks.nonce
                    }
                });
                
                setPreview(response.html);
            } catch (error) {
                console.error('YAP Block Preview Error:', error);
                setPreview('<p>Error loading preview</p>');
            }
        };

        const toggleMode = () => {
            const newMode = mode === 'preview' ? 'edit' : 'preview';
            setMode(newMode);
            setAttributes({ mode: newMode });
        };

        return (
            <Fragment>
                {/* Block Controls (top toolbar) */}
                <BlockControls>
                    <ToolbarGroup>
                        <ToolbarButton
                            icon={mode === 'preview' ? 'edit' : 'visibility'}
                            label={mode === 'preview' ? __('Switch to Edit', 'yap') : __('Switch to Preview', 'yap')}
                            onClick={toggleMode}
                        />
                    </ToolbarGroup>
                </BlockControls>

                {/* Inspector Controls (sidebar) */}
                <InspectorControls>
                    <PanelBody title={__('Block Settings', 'yap')} initialOpen={true}>
                        <PanelRow>
                            <ButtonGroup>
                                <Button
                                    isPrimary={mode === 'edit'}
                                    onClick={() => {
                                        setMode('edit');
                                        setAttributes({ mode: 'edit' });
                                    }}
                                >
                                    {__('Edit', 'yap')}
                                </Button>
                                <Button
                                    isPrimary={mode === 'preview'}
                                    onClick={() => {
                                        setMode('preview');
                                        setAttributes({ mode: 'preview' });
                                    }}
                                >
                                    {__('Preview', 'yap')}
                                </Button>
                            </ButtonGroup>
                        </PanelRow>
                    </PanelBody>

                    {/* Fields Panel */}
                    {mode === 'edit' && (
                        <PanelBody title={__('Fields', 'yap')} initialOpen={true}>
                            {blockConfig.fields.map(field => (
                                <YAPFieldControl
                                    key={field.name}
                                    field={field}
                                    value={attributes[field.name]}
                                    onChange={(value) => setAttributes({ [field.name]: value })}
                                />
                            ))}
                        </PanelBody>
                    )}
                </InspectorControls>

                {/* Block Content */}
                <div {...blockProps}>
                    {mode === 'edit' ? (
                        <YAPBlockEditMode 
                            blockConfig={blockConfig} 
                            attributes={attributes} 
                            setAttributes={setAttributes} 
                        />
                    ) : (
                        <YAPBlockPreviewMode 
                            blockConfig={blockConfig} 
                            preview={preview} 
                        />
                    )}
                </div>
            </Fragment>
        );
    }

    /**
     * Edit mode - show editable fields
     */
    function YAPBlockEditMode({ blockConfig, attributes, setAttributes }) {
        return (
            <div className="yap-block-edit-mode">
                <div className="yap-block-header">
                    <h3>{blockConfig.title}</h3>
                    <span className="yap-block-icon">
                        <span className={`dashicons dashicons-${blockConfig.icon}`}></span>
                    </span>
                </div>
                
                <div className="yap-block-fields">
                    {blockConfig.fields.map(field => (
                        <div key={field.name} className="yap-block-field">
                            <YAPFieldControl
                                field={field}
                                value={attributes[field.name]}
                                onChange={(value) => setAttributes({ [field.name]: value })}
                            />
                        </div>
                    ))}
                </div>
                
                {blockConfig.supports.jsx && (
                    <div className="yap-block-inner-blocks">
                        <InnerBlocks />
                    </div>
                )}
            </div>
        );
    }

    /**
     * Preview mode - show rendered block
     */
    function YAPBlockPreviewMode({ blockConfig, preview }) {
        return (
            <div className="yap-block-preview-mode">
                {preview ? (
                    <div dangerouslySetInnerHTML={{ __html: preview }} />
                ) : (
                    <div className="yap-block-loading">
                        <span className="spinner is-active"></span>
                        <p>{__('Loading preview...', 'yap')}</p>
                    </div>
                )}
                
                {blockConfig.supports.jsx && (
                    <div className="yap-block-inner-blocks">
                        <InnerBlocks />
                    </div>
                )}
            </div>
        );
    }

    /**
     * Field control component - renders appropriate input for field type
     */
    function YAPFieldControl({ field, value, onChange }) {
        switch (field.type) {
            case 'short_text':
                return (
                    <TextControl
                        label={field.label}
                        value={value || ''}
                        onChange={onChange}
                        placeholder={field.options?.placeholder}
                        help={field.options?.help}
                    />
                );

            case 'long_text':
                return (
                    <TextareaControl
                        label={field.label}
                        value={value || ''}
                        onChange={onChange}
                        rows={field.options?.rows || 4}
                        help={field.options?.help}
                    />
                );

            case 'number':
                return (
                    <TextControl
                        label={field.label}
                        type="number"
                        value={value || ''}
                        onChange={onChange}
                        min={field.options?.min}
                        max={field.options?.max}
                        step={field.options?.step}
                    />
                );

            case 'range':
                return (
                    <RangeControl
                        label={field.label}
                        value={value || field.options?.default_value || 0}
                        onChange={onChange}
                        min={field.options?.min || 0}
                        max={field.options?.max || 100}
                        step={field.options?.step || 1}
                    />
                );

            case 'true_false':
                return (
                    <ToggleControl
                        label={field.label}
                        checked={!!value}
                        onChange={onChange}
                        help={field.options?.help}
                    />
                );

            case 'select':
                return (
                    <SelectControl
                        label={field.label}
                        value={value || ''}
                        onChange={onChange}
                        options={[
                            { label: '-- Select --', value: '' },
                            ...(field.options?.choices || []).map(choice => ({
                                label: choice.label || choice,
                                value: choice.value || choice
                            }))
                        ]}
                    />
                );

            case 'color':
                return (
                    <PanelRow>
                        <label>{field.label}</label>
                        <ColorPicker
                            color={value || field.options?.default_value}
                            onChangeComplete={(color) => onChange(color.hex)}
                        />
                    </PanelRow>
                );

            case 'image':
                return (
                    <MediaUploadCheck>
                        <MediaUpload
                            onSelect={(media) => onChange(media.id)}
                            allowedTypes={['image']}
                            value={value}
                            render={({ open }) => (
                                <div className="yap-image-field">
                                    <label>{field.label}</label>
                                    {value ? (
                                        <div className="yap-image-preview">
                                            <img src={getMediaUrl(value)} alt="" />
                                            <Button
                                                isDestructive
                                                onClick={() => onChange(null)}
                                            >
                                                {__('Remove', 'yap')}
                                            </Button>
                                        </div>
                                    ) : (
                                        <Button isPrimary onClick={open}>
                                            {__('Select Image', 'yap')}
                                        </Button>
                                    )}
                                </div>
                            )}
                        />
                    </MediaUploadCheck>
                );

            case 'wysiwyg':
                // For wysiwyg, we'll use a simple textarea in block editor
                // Full WYSIWYG editor requires RichText component
                return (
                    <TextareaControl
                        label={field.label}
                        value={value || ''}
                        onChange={onChange}
                        rows={10}
                        help={__('Full editor available in post meta', 'yap')}
                    />
                );

            default:
                return (
                    <TextControl
                        label={field.label}
                        value={value || ''}
                        onChange={onChange}
                    />
                );
        }
    }

    /**
     * Get block attributes schema
     */
    function getBlockAttributes(blockConfig) {
        const attributes = {
            mode: {
                type: 'string',
                default: blockConfig.mode || 'preview'
            },
            align: {
                type: 'string'
            },
            className: {
                type: 'string'
            },
            anchor: {
                type: 'string'
            }
        };

        // Add attributes for each field
        blockConfig.fields.forEach(field => {
            attributes[field.name] = {
                type: getAttributeType(field.type),
                default: field.default_value || null
            };
        });

        return attributes;
    }

    /**
     * Map field type to attribute type
     */
    function getAttributeType(fieldType) {
        const typeMap = {
            'short_text': 'string',
            'long_text': 'string',
            'number': 'number',
            'range': 'number',
            'true_false': 'boolean',
            'image': 'number',
            'file': 'number',
            'gallery': 'array',
            'select': 'string',
            'checkbox': 'array',
            'radio': 'string',
            'color': 'string',
            'wysiwyg': 'string'
        };

        return typeMap[fieldType] || 'string';
    }

    /**
     * Get media URL by ID
     */
    function getMediaUrl(mediaId) {
        const media = useSelect(select => 
            select('core').getMedia(mediaId)
        );
        
        return media?.source_url || '';
    }

})(window.wp);
