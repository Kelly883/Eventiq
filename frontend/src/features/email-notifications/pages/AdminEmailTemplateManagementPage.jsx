import React, { useState } from 'react';
import TemplateEditor from '../components/TemplateEditor';

const AdminEmailTemplateManagementPage = () => {
  // TODO: replace with useEmailTemplates() once it fetches real data;
  // this just proves the editor renders and holds content for now.
  const [mjmlContent, setMjmlContent] = useState(
    '<pre><code class="language-mjml">&lt;mjml&gt;\n  &lt;mj-body&gt;\n    &lt;mj-section&gt;\n      &lt;mj-column&gt;\n        &lt;mj-text&gt;Your ticket is confirmed!&lt;/mj-text&gt;\n      &lt;/mj-column&gt;\n    &lt;/mj-section&gt;\n  &lt;/mj-body&gt;\n&lt;/mjml&gt;</code></pre>'
  );

  return (
    <div className="p-6 max-w-4xl mx-auto">
      <h1 className="text-xl font-bold mb-4">Email Templates</h1>
      <TemplateEditor content={mjmlContent} onChange={setMjmlContent} />
    </div>
  );
};

export default AdminEmailTemplateManagementPage;
