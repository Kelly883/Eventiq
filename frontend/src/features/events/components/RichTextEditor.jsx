import React, { useEffect, useRef } from 'react';

const RichTextEditor = ({ value = '', onChange, placeholder = 'Describe your event...', maxLength = 5000, disabled = false }) => {
  const editorRef = useRef(null);

  useEffect(() => {
    if (editorRef.current && editorRef.current.innerHTML !== value) {
      editorRef.current.innerHTML = value;
    }
  }, [value]);

  const handleInput = () => {
    if (!editorRef.current) return;
    const html = editorRef.current.innerHTML;
    onChange?.(html);
  };

  const execCommand = (command, commandValue = null) => {
    document.execCommand(command, false, commandValue);
    editorRef.current?.focus();
    handleInput();
  };

  const charCount = editorRef.current?.innerText?.length ?? 0;

  return (
    <div className="rich-text-editor">
      <div className="rich-text-editor__toolbar">
        <button type="button" onClick={() => execCommand('bold')} className="rich-text-editor__tool" aria-label="Bold" disabled={disabled}><strong>B</strong></button>
        <button type="button" onClick={() => execCommand('italic')} className="rich-text-editor__tool" aria-label="Italic" disabled={disabled}><em>I</em></button>
        <button type="button" onClick={() => execCommand('underline')} className="rich-text-editor__tool" aria-label="Underline" disabled={disabled}><u>U</u></button>
        <button type="button" onClick={() => execCommand('insertUnorderedList')} className="rich-text-editor__tool" aria-label="Bullet list" disabled={disabled}>• List</button>
      </div>
      <div
        ref={editorRef}
        contentEditable={!disabled}
        onInput={handleInput}
        data-placeholder={placeholder}
        className="rich-text-editor__content"
        role="textbox"
        aria-multiline="true"
        aria-label={placeholder}
        tabIndex={0}
        dangerouslySetInnerHTML={{ __html: value }}
      />
      <p className="rich-text-editor__count" aria-live="off">{charCount}/{maxLength}</p>
    </div>
  );
};

export default RichTextEditor;
