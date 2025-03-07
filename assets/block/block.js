import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';

registerBlockType('like-dislike-button/block', {
    title: 'Like/Dislike Button',
    icon: 'thumbs-up',
    category: 'widgets',
    
    attributes: {
        postId: {
            type: 'number',
            default: 0
        }
    },

    edit: ({ attributes }) => {
        return (
            <div {...useBlockProps()}>
                <p>Like/Dislike Buttons</p>
            </div>
        );
    },

    save: () => null
});
