import { useForm, type InertiaFormProps } from '@inertiajs/react';
import React, { useCallback, useState } from 'react';

type UseModalFormOptions<TItem, TForm> = {
    initialData: TForm;
    onOpen?: (item: TItem, form: InertiaFormProps<TForm>) => void;
    onClose?: (form: InertiaFormProps<TForm>) => void;
};

type UseModalFormReturn<TItem, TForm> = {
    isOpen: boolean;
    selected: TItem | null;
    form: InertiaFormProps<TForm>;
    open: (item: TItem) => void;
    close: () => void;
    submit: (
        handler: (form: InertiaFormProps<TForm>, item: TItem) => void,
    ) => (event: React.FormEvent) => void;
};

export function useModalForm<TItem, TForm>(
    options: UseModalFormOptions<TItem, TForm>,
): UseModalFormReturn<TItem, TForm> {
    const { initialData, onOpen, onClose } = options;
    const form = useForm<TForm>(initialData);
    const [isOpen, setIsOpen] = useState(false);
    const [selected, setSelected] = useState<TItem | null>(null);

    const open = useCallback(
        (item: TItem) => {
            setSelected(item);
            onOpen?.(item, form);
            setIsOpen(true);
        },
        [form, onOpen],
    );

    const close = useCallback(() => {
        setIsOpen(false);
        setSelected(null);
        onClose?.(form);
    }, [form, onClose]);

    const submit = useCallback(
        (handler: (form: InertiaFormProps<TForm>, item: TItem) => void) =>
            (event: React.FormEvent) => {
                event.preventDefault();
                if (!selected) {
                    return;
                }
                handler(form, selected);
            },
        [form, selected],
    );

    return {
        isOpen,
        selected,
        form,
        open,
        close,
        submit,
    };
}
