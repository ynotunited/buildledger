type CollectionEnvelope<T> = {
  data?: T[];
  meta?: {
    current_page?: number;
    last_page?: number;
    total?: number;
  };
  current_page?: number;
  last_page?: number;
  total?: number;
};

type ResourceEnvelope<T> = {
  data?: T;
};

export function extractCollection<T>(payload: T[] | CollectionEnvelope<T>): T[] {
  if (Array.isArray(payload)) {
    return payload;
  }

  return Array.isArray(payload?.data) ? payload.data : [];
}

export function extractResource<T>(payload: T | ResourceEnvelope<T>): T {
  if (
    payload &&
    typeof payload === "object" &&
    "data" in payload &&
    !Array.isArray(payload.data)
  ) {
    return payload.data as T;
  }

  return payload as T;
}

export function extractPagination(payload: CollectionEnvelope<unknown>) {
  return {
    currentPage: payload.meta?.current_page ?? payload.current_page ?? 1,
    lastPage: payload.meta?.last_page ?? payload.last_page ?? 1,
    total: payload.meta?.total ?? payload.total ?? 0,
  };
}
