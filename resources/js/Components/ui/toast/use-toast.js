import { computed, ref } from "vue";

const TOAST_LIMIT = 1;
const TOAST_REMOVE_DELAY = 1000000; // Keep this unchanged for exit animation
const DEFAULT_TOAST_DURATION = 5000; // Default duration: 5 seconds

const actionTypes = {
  ADD_TOAST: "ADD_TOAST",
  UPDATE_TOAST: "UPDATE_TOAST",
  DISMISS_TOAST: "DISMISS_TOAST",
  REMOVE_TOAST: "REMOVE_TOAST",
};

let count = 0;

function genId() {
  count = (count + 1) % Number.MAX_VALUE;
  return count.toString();
}

const toastTimeouts = new Map();

function addToRemoveQueue(toastId) {
  if (toastTimeouts.has(toastId)) {
    clearTimeout(toastTimeouts.get(toastId));
  }

  const timeout = setTimeout(() => {
    toastTimeouts.delete(toastId);
    dispatch({
      type: actionTypes.REMOVE_TOAST,
      toastId,
    });
  }, TOAST_REMOVE_DELAY);

  toastTimeouts.set(toastId, timeout);
}

function addToDismissQueue(toastId, duration) {
  setTimeout(() => {
    dispatch({ type: actionTypes.DISMISS_TOAST, toastId });
  }, duration);
}

const state = ref({
  toasts: [],
});

function dispatch(action) {
  switch (action.type) {
    case actionTypes.ADD_TOAST:
      state.value.toasts = [action.toast, ...state.value.toasts].slice(
        0,
        TOAST_LIMIT,
      );
      addToDismissQueue(action.toast.id, action.toast.duration || DEFAULT_TOAST_DURATION);
      break;

    case actionTypes.UPDATE_TOAST:
      state.value.toasts = state.value.toasts.map((t) =>
        t.id === action.toast.id ? { ...t, ...action.toast } : t,
      );
      if (action.toast.duration) {
        addToDismissQueue(action.toast.id, action.toast.duration);
      }
      break;

    case actionTypes.DISMISS_TOAST: {
      const { toastId } = action;

      if (toastId) {
        addToRemoveQueue(toastId);
      } else {
        state.value.toasts.forEach((toast) => {
          addToRemoveQueue(toast.id);
        });
      }

      state.value.toasts = state.value.toasts.map((t) =>
        t.id === toastId || toastId === undefined
          ? {
              ...t,
              open: false,
            }
          : t,
      );
      break;
    }

    case actionTypes.REMOVE_TOAST:
      if (action.toastId === undefined) state.value.toasts = [];
      else
        state.value.toasts = state.value.toasts.filter(
          (t) => t.id !== action.toastId,
        );

      break;
  }
}

function useToast() {
  return {
    toasts: computed(() => state.value.toasts),
    toast,
    dismiss: (toastId) =>
      dispatch({ type: actionTypes.DISMISS_TOAST, toastId }),
  };
}

function toast(props) {
  const id = genId();

  const update = (props) =>
    dispatch({
      type: actionTypes.UPDATE_TOAST,
      toast: { ...props, id },
    });

  const dismiss = () =>
    dispatch({ type: actionTypes.DISMISS_TOAST, toastId: id });

  dispatch({
    type: actionTypes.ADD_TOAST,
    toast: {
      ...props,
      id,
      open: true,
      onOpenChange: (open) => {
        if (!open) dismiss();
      },
    },
  });

  return {
    id,
    dismiss,
    update,
  };
}

export { toast, useToast };