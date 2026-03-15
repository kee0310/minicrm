type FilterSearchProps = {
    value: string;
    onChange: (value: string) => void;
    onSubmit: (event: React.FormEvent<HTMLFormElement>) => void;
    onClear: () => void;
    placeholder?: string;
    className?: string;
};

export function CrmFilterSearch({
    value,
    onChange,
    onSubmit,
    onClear,
    placeholder,
    className,
}: FilterSearchProps) {
    return (
        <form onSubmit={onSubmit} className={className ?? 'w-full'}>
            <div className="crm-filter-search-row">
                <div className="crm-filter-search-input-wrap">
                    <input
                        type="text"
                        className="crm-filter-search-input"
                        placeholder={placeholder}
                        value={value}
                        onChange={(event) => onChange(event.target.value)}
                    />
                    <button
                        type="button"
                        onClick={onClear}
                        className={`crm-filter-search-clear ${
                            value ? 'opacity-100' : 'opacity-0 pointer-events-none'
                        }`}
                        aria-label="Clear search"
                    >
                        <i className="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <button
                    type="submit"
                    className="crm-btn-secondary crm-filter-search-submit"
                    aria-label="Search"
                >
                    <i className="fa-solid fa-magnifying-glass"></i>
                </button>
            </div>
        </form>
    );
}
