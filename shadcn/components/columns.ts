import { h } from 'vue'
import { ColumnDef } from "@tanstack/vue-table";
import { ArrowUpDown } from 'lucide-vue-next'

export const columns: ColumnDef<ProductEntry>[] = [
    {
        accessorKey: 'sheet',
        header: ({ column }) => {
            return h('div', { class: 'flex justify-start' }, [
                h('span', 'Sheet'),
                h(ArrowUpDown, {
                    class: 'h-4 w-4 font-bold',
                    style: "cursor: 'pointer'; margin-left:10px",
                    onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
                }),
            ])
        },
        cell: ({ row }) => h('div', row.getValue('sheet')),
    },
    {
        accessorKey: 'ean',
        header: ({ column }) => {
            return h('div', { class: 'flex justify-start' }, [
                h('span', 'EAN'),
                h(ArrowUpDown, {
                    class: 'h-4 w-4',
                    style: "cursor: 'pointer'; margin-left:10px",
                    onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
                }),
            ])
        },
        cell: ({ row }) => h('div', { class: 'font-semibold' }, row.getValue('ean')),
    },
    {
        accessorKey: 'description',
        header: ({ column }) => {
            return h('div', { class: 'flex justify-start' }, [
                h('span', 'Description'),
                h(ArrowUpDown, {
                    class: 'h-4 w-4',
                    style: "cursor: 'pointer'; margin-left:10px",
                    onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
                }),
            ])
        },
        cell: ({ row }) => h('div', { class: 'font-semibold' }, row.getValue('description')),
    },
    {
        accessorKey: 'stock',
        header: ({ column }) => {
            return h('div', { class: 'flex justify-start text-right' }, [
                h('span', 'Stock'),
                h(ArrowUpDown, {
                    class: 'h-4 w-4',
                    style: "cursor: 'pointer'; margin-left:10px",
                    onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
                }),
            ])
        },

        cell: ({ row }) => {
            const stockValue = Number.parseFloat(row.getValue('stock'))
            const textColorClass = stockValue === 0 ? 'text-red-500' : 'text-green-500'
            return h('div', { class: ['text-right', 'font-medium', textColorClass] }, stockValue)
        },
    },
    {
        accessorKey: 'price',
        header: ({ column }) => {
            return h('div', { class: 'flex justify-start text-right' }, [
                h('span', 'Price'),
                h(ArrowUpDown, {
                    class: 'h-4 w-4',
                    style: "cursor: 'pointer'; margin-left:10px",
                    onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
                }),
            ])
        },
        cell: ({ row }) => {
            const amount = Number.parseFloat(row.getValue('price'))
            const formatted = new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'GBP',
            }).format(amount)


            return h('div', { class: ['text-right', 'font-medium'] }, formatted)
        },
    }
]
