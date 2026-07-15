import StarterKit from '@tiptap/starter-kit';
import Link from '@tiptap/extension-link';
import CodeBlockLowlight from '@tiptap/extension-code-block-lowlight';
import { createLowlight, common } from 'lowlight';

/**
 * lowlight/highlight.js has no dedicated "mjml" grammar (MJML isn't a
 * standard language it ships support for). Registering 'mjml' as an
 * alias of the 'xml' grammar is a reasonable approximation - MJML is
 * XML-based markup - not a purpose-built MJML highlighter.
 */
const lowlight = createLowlight(common);
lowlight.register('mjml', common.xml);

/**
 * Extensions for TemplateEditor: bold/italic/lists come from StarterKit,
 * links from the Link extension, and MJML/HTML code blocks with syntax
 * highlighting via CodeBlockLowlight. StarterKit's default codeBlock is
 * disabled to avoid a duplicate-extension conflict with CodeBlockLowlight.
 */
export const templateEditorExtensions = [
  StarterKit.configure({
    codeBlock: false,
  }),
  Link.configure({
    openOnClick: false,
    autolink: true,
  }),
  CodeBlockLowlight.configure({
    lowlight,
    defaultLanguage: 'mjml',
  }),
];
