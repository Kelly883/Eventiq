import React from 'react';
import { useEditor, EditorContent } from '@tiptap/react';
import { templateEditorExtensions } from '../utils/tiptapConfig';

const ToolbarButton = ({ onClick, active, children, label }) => (
  <button
    type="button"
    onClick={onClick}
    aria-label={label}
    aria-pressed={active}
    className={`px-2.5 py-1.5 rounded text-sm font-medium ${
      active ? 'bg-indigo-100 text-indigo-700' : 'text-slate-600 hover:bg-slate-100'
    }`}
  >
    {children}
  </button>
);

/**
 * Rich-text/MJML editor for admin email templates. Supports bold,
 * italic, links, lists, and MJML/HTML code blocks with syntax
 * highlighting (see tiptapConfig.js for extension setup).
 */
const TemplateEditor = ({ content = '', onChange }) => {
  const editor = useEditor({
    extensions: templateEditorExtensions,
    content,
    onUpdate: ({ editor }) => {
      onChange?.(editor.getHTML());
    },
  });

  if (!editor) return null;

  return (
    <div className="border border-slate-200 rounded-lg overflow-hidden">
      <div className="flex items-center gap-1 border-b border-slate-200 bg-slate-50 px-2 py-1.5">
        <ToolbarButton
          label="Bold"
          active={editor.isActive('bold')}
          onClick={() => editor.chain().focus().toggleBold().run()}
        >
          B
        </ToolbarButton>
        <ToolbarButton
          label="Italic"
          active={editor.isActive('italic')}
          onClick={() => editor.chain().focus().toggleItalic().run()}
        >
          I
        </ToolbarButton>
        <ToolbarButton
          label="Bullet list"
          active={editor.isActive('bulletList')}
          onClick={() => editor.chain().focus().toggleBulletList().run()}
        >
          • List
        </ToolbarButton>
        <ToolbarButton
          label="Ordered list"
          active={editor.isActive('orderedList')}
          onClick={() => editor.chain().focus().toggleOrderedList().run()}
        >
          1. List
        </ToolbarButton>
        <ToolbarButton
          label="Link"
          active={editor.isActive('link')}
          onClick={() => {
            const url = window.prompt('URL');
            if (url) editor.chain().focus().setLink({ href: url }).run();
          }}
        >
          Link
        </ToolbarButton>
        <ToolbarButton
          label="MJML/code block"
          active={editor.isActive('codeBlock')}
          onClick={() => editor.chain().focus().toggleCodeBlock().run()}
        >
          {'</>'} MJML
        </ToolbarButton>
      </div>
      <EditorContent editor={editor} className="prose max-w-none p-4 min-h-[300px]" />
    </div>
  );
};

export default TemplateEditor;
