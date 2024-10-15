// useTable.js
import { ref, computed } from 'vue';
import { useVueTable, getCoreRowModel, getPaginationRowModel } from '@tanstack/vue-table';
import axios from 'axios';

export default function useTable(columns, apiEndpoint) {
  const data = ref([]);
  const globalFilter = ref('');
  const pagination = ref({
    pageIndex: 0,
    pageSize: 10,
    pageCount: 1,
    total: 0,
  });

  const sorting = ref({ field: 'id', direction: 'asc' });

  const table = useVueTable({
    get data() { return data.value; },
    columns,
    getCoreRowModel: getCoreRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    state: {
      pagination: computed(() => ({
        pageIndex: pagination.value.pageIndex,
        pageSize: pagination.value.pageSize,
      })),
    },
    manualPagination: true,
    pageCount: computed(() => pagination.value.pageCount),
    onPaginationChange: (updater) => {
      if (typeof updater === 'function') {
        const newPagination = updater(pagination.value);
        pagination.value = { ...pagination.value, ...newPagination };
      } else {
        pagination.value = { ...pagination.value, ...updater };
      }
      fetchData();
    }
  });

  const fetchData = async () => {
    try {
      const response = await axios.get(apiEndpoint, {
        params: {
          search: globalFilter.value,
          page: pagination.value.pageIndex + 1,
          per_page: pagination.value.pageSize,
          sort: sorting.value.field,
          direction: sorting.value.direction,
        }
      });

      data.value = response.data.data;
      pagination.value = {
        pageIndex: response.data.meta.current_page - 1,
        pageSize: response.data.meta.per_page,
        pageCount: response.data.meta.last_page,
        total: response.data.meta.total,
      };
    } catch (error) {
      console.error('Error fetching data:', error);
    }
  };

  const sortBy = (field) => {
    if (sorting.value.field === field) {
      sorting.value.direction = sorting.value.direction === 'asc' ? 'desc' : 'asc';
    } else {
      sorting.value.field = field;
      sorting.value.direction = 'asc';
    }
    fetchData();
  };

  return {
    table,
    data,
    globalFilter,
    pagination,
    sorting,
    fetchData,
    sortBy,
  };
}