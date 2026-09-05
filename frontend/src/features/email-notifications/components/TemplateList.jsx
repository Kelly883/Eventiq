import React from 'react';

const TemplateList = ({ templates, selectedTemplate, onSelect, searchQuery, onSearchChange }) => {
  const filteredTemplates = React.useMemo(() => {
    if (!templates) return [];
    if (!searchQuery?.trim()) return templates;
    const query = searchQuery.toLowerCase();
    return templates.filter(
      (t) =>
        t.name?.toLowerCase().includes(query) ||
        t.key?.toLowerCase().includes(query) ||
        t.subject?.toLowerCase().includes(query)
    );
  }, [templates, searchQuery]);

  return (
    <div className="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
      <div className="p-4 border-b border-slate-100">
        <h2 className="font-semibold text-slate-800">Templates</h2>
        <p className="text-xs text-slate-500 mt-0.5">{filteredTemplates.length} of {templates?.length || 0} templates</p>
      </div>
      <div className="p-3 border-b border-slate-100">
        <div className="relative">
          <svg className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 9 0 0114 0z" />
          </svg>
          <input
            type="text"
            value={searchQuery}
            onChange={(e) => onSearchChange?.(e.target.value)}
            placeholder="Search templates..."
            className="w-full pl-9 pr-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
          />
          {searchQuery && (
            <button
              onClick={() => onSearchChange?.('')}
              className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
            >
              <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          )}
        </div>
      </div>
      <div className="divide-y divide-slate-100 max-h-[500px] overflow-y-auto">
        {filteredTemplates.length > 0 ? (
          filteredTemplates.map((template) => (
            <button
              key={template.id}
              onClick={() => onSelect(template)}
              className={`w-full text-left p-4 hover:bg-slate-50 transition-colors ${
                selectedTemplate?.id === template.id ? 'bg-indigo-50 border-l-4 border-indigo-600' : ''
              }`}
            >
              <div className="font-medium text-slate-800">{template.name}</div>
              <div className="text-xs text-slate-500 mt-0.5">{template.subject}</div>
              <div className="flex items-center gap-2 mt-2 flex-wrap">
                <span className={`text-[10px] font-medium px-2 py-0.5 rounded-full ${
                  template.status === 'active'
                    ? 'bg-emerald-100 text-emerald-700'
                    : 'bg-slate-100 text-slate-600'
                }`}>
                  {template.status === 'active' ? 'Active' : 'Draft'}
                </span>
                <span className="text-[10px] text-slate-400">{template.key}</span>
                {(template.creator?.name || template.created_at) && (
                  <span className="text-[10px] text-slate-400 ml-auto" title={template.created_at ? `Created ${new Date(template.created_at).toLocaleString()}` : undefined}>
                    {template.creator?.name ? `by ${template.creator.name}` : ''}
                    {template.creator?.name && template.created_at ? ' • ' : ''}
                    {template.created_at ? new Date(template.created_at).toLocaleDateString() : ''}
                  </span>
                )}
              </div>
            </button>
          ))
        ) : (
          <div className="p-4 text-center text-slate-500 text-sm">
            {searchQuery ? 'No templates match your search.' : 'No templates found. Create your first email template.'}
          </div>
        )}
      </div>
    </div>
  );
};

export default TemplateList;
