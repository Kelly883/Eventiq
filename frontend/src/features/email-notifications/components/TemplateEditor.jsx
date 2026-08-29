import React, { useState } from 'react';
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
  const [mjmlTooltipOpen, setMjmlTooltipOpen] = useState(false);

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
        {/* MJML block button with explanation tooltip */}
        <div className="relative">
          <ToolbarButton
            label="MJML code block — click for help"
            active={editor.isActive('codeBlock')}
            onClick={() => editor.chain().focus().toggleCodeBlock().run()}
          >
            {'</>'} MJML
          </ToolbarButton>
          {/* Tooltip explaining MJML */}
          <div
            className="absolute bottom-full left-0 mb-2 w-64 bg-slate-900 text-white text-xs rounded-lg shadow-lg p-3 z-50"
            style={{ display: mjmlTooltipOpen ? 'block' : 'none' }}
          >
            <p className="font-semibold mb-1">What is MJML?</p>
            <p className="leading-relaxed opacity-90">
              MJML (Mailjet Markup Language) is a responsive email standard that
              automatically renders correctly across email clients. Use the{' '}
              <code className="bg-slate-700 px-1 rounded">&lt;/&gt;</code> MJML
              button to insert a code block for custom email layouts. For plain
              rich-text content, use the formatting buttons above.
            </p>
            <button
              type="button"
              className="mt-2 text-indigo-300 hover:text-indigo-200 font-medium"
              onClick={() => setMjmlTooltipOpen(false)}
            >
              Got it
            </button>
            {/* Arrow */}
            <div className="absolute top-full left-3 -mt-px w-0 h-0 border-l-[6px] border-r-[6px] border-t-[6px] border-l-transparent border-r-transparent border-t-slate-900" />
          </div>
          {/* Info trigger */}
          <button
            type="button"
            className="ml-1 text-slate-400 hover:text-indigo-500 cursor-help text-xs font-bold"
            onClick={() => setMjmlTooltipOpen((v) => !v)}
            aria-label="What is MJML?"
          >
            ?
          </button>
        </div>
      </div>
      <EditorContent editor={editor} className="prose max-w-none p-4 min-h-[300px]" />
    </div>
  );
};

export default TemplateEditor;
