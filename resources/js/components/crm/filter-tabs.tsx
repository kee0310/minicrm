type FilterTabItem = {
    label: string;
    value: string;
    active: boolean;
    variant?: 'all' | 'stage';
    onClick: () => void;
};

type FilterTabsProps = {
    items: FilterTabItem[];
};

export function CrmFilterTabs({ items }: FilterTabsProps) {
    if (!items.length) {
        return null;
    }

    return (
        <div className="crm-filter-tabs-scroll scrollbar-hide">
            <div className="crm-filter-tabs">
                {items.map((item) => (
                    <button
                        key={`${item.label}-${item.value}`}
                        type="button"
                        className={`crm-filter-tab ${
                            item.variant === 'all'
                                ? 'crm-filter-tab-all'
                                : 'crm-filter-tab-stage'
                        } ${item.active ? 'crm-filter-tab-active' : 'crm-filter-tab-inactive'}`}
                        onClick={item.onClick}
                    >
                        {item.label}
                    </button>
                ))}
            </div>
        </div>
    );
}
