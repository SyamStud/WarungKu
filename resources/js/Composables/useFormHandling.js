// useFormHandling.js
import { ref, computed } from 'vue'
import { useForm } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import * as z from 'zod'
import { useToast } from '@/Composables/useToast'

export function useFormHandling(addSchema, editSchema, submitUrl, fetchData) {
  const Toast = useToast()
  const isEdit = ref(false)
  const selectedItem = ref(null)

  const formSchema = computed(() => isEdit.value ? editSchema : addSchema)

  const form = useForm({
    validationSchema: computed(() => toTypedSchema(formSchema.value)),
  })

  const onSubmit = form.handleSubmit(async (values) => {
    try {
      const url = isEdit.value ? `${submitUrl}/${selectedItem.value.id}` : submitUrl
      const method = isEdit.value ? 'put' : 'post'
      
      const response = await axios[method](url, values, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      })

      if (response.data.status === 'error') {
        Toast.fire({
          icon: "error",
          title: response.data.message,
        })
      } else {
        Toast.fire({
          icon: "success",
          title: response.data.message,
        })
        fetchData()
      }
    } catch (error) {
      console.error('Error submitting form:', error)
    }
  })

  return {
    isEdit,
    selectedItem,
    form,
    onSubmit,
  }
}