import React from 'react';

interface BreadcrumbProps {
  items: { label: string; href?: string }[];
  currentItem?: { label: string; href?: string };
  separator?: string;
  className?: string;
}

const Breadcrumb = ({
  items,
  currentItem,
  separator = '>',
  className,
}: BreadcrumbProps) => {
  return (
    <nav aria-label="breadcrumb" className={`flex items-center space-x-2 ${className}`}>
      {items.map((item, index) => (
        <span key={index} className="text-sm text-gray-500">
          {item.href ? (
            <a
              href={item.href}
              className="hover:text-primary-600 transition-colors"
              rel="noopener noreferrer"
            >
              {item.label}
            </a>
          ) : (
            <span className="text-gray-500 truncate max-w-xs">{item.label}</span>
          ))}
          {index < items.length - 1 && separator && (
            <span className="mx-2 text-gray-400">
              {separator}
            </span>
          )}
        </span>
      ))}
      {currentItem && (
        <span className="text-sm text-gray-400 truncate max-w-xs">
          {currentItem.label}
        </span>
      )}
    </nav>
  );
};

export default React.memo(Breadcrumb);
export { Breadcrumb };