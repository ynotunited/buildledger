"use client";

import { ChangeEvent, FormEvent, useEffect, useState } from "react";
import { AxiosError } from "axios";
import AppShell from "@/components/layout/AppShell";
import axiosInstance from "@/lib/axios";

type Company = {
  id?: number;
  name: string;
  email: string | null;
  phone: string | null;
  address: string | null;
  website: string | null;
  tax_id: string | null;
  industry: string | null;
  currency: string | null;
  logo_url: string | null;
};

type ValidationErrors = Record<string, string[]>;

const EMPTY_COMPANY: Company = {
  name: "",
  email: "",
  phone: "",
  address: "",
  website: "",
  tax_id: "",
  industry: "",
  currency: "NGN",
  logo_url: null,
};

function getErrorMessage(error: unknown, fallback: string) {
  if (error instanceof AxiosError) {
    return error.response?.data?.message ?? fallback;
  }

  return fallback;
}

function getValidationErrors(error: unknown): ValidationErrors {
  if (error instanceof AxiosError) {
    return (error.response?.data?.errors as ValidationErrors | undefined) ?? {};
  }

  return {};
}

export default function CompanyPage() {
  const [company, setCompany] = useState<Company>(EMPTY_COMPANY);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState<string | null>(null);
  const [errors, setErrors] = useState<ValidationErrors>({});
  const [logoFile, setLogoFile] = useState<File | null>(null);
  const [removeLogo, setRemoveLogo] = useState(false);
  const [logoPreview, setLogoPreview] = useState<string | null>(null);

  useEffect(() => {
    let mounted = true;

    const loadCompany = async () => {
      try {
        const response = await axiosInstance.get<{ data: Company | null }>("/company");
        if (!mounted) {
          return;
        }

        const current = response.data.data;
        if (current) {
          setCompany({
            ...EMPTY_COMPANY,
            ...current,
          });
          setLogoPreview(current.logo_url);
        }
      } catch (error) {
        if (mounted) {
          setMessage(getErrorMessage(error, "We couldn't load your company settings right now."));
        }
      } finally {
        if (mounted) {
          setLoading(false);
        }
      }
    };

    void loadCompany();

    return () => {
      mounted = false;
    };
  }, []);

  const updateField = (field: keyof Company, value: string) => {
    setCompany((current) => ({
      ...current,
      [field]: value,
    }));
  };

  const handleLogoChange = (event: ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0] ?? null;
    setLogoFile(file);
    setRemoveLogo(false);

    if (file) {
      setLogoPreview(URL.createObjectURL(file));
    } else {
      setLogoPreview(company.logo_url);
    }
  };

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setSaving(true);
    setMessage(null);
    setErrors({});

    const formData = new FormData();
    formData.append("name", company.name);
    formData.append("email", company.email ?? "");
    formData.append("phone", company.phone ?? "");
    formData.append("address", company.address ?? "");
    formData.append("website", company.website ?? "");
    formData.append("tax_id", company.tax_id ?? "");
    formData.append("industry", company.industry ?? "");
    formData.append("currency", company.currency ?? "NGN");
    formData.append("remove_logo", removeLogo ? "1" : "0");

    if (logoFile) {
      formData.append("logo", logoFile);
    }

    try {
      const response = await axiosInstance.post<{ message: string; data: Company }>("/company", formData, {
        headers: {
          "Content-Type": "multipart/form-data",
        },
      });

      setCompany({
        ...EMPTY_COMPANY,
        ...response.data.data,
      });
      setLogoFile(null);
      setRemoveLogo(false);
      setLogoPreview(response.data.data.logo_url);
      setMessage(response.data.message ?? "Company settings updated successfully.");
    } catch (error) {
      setMessage(getErrorMessage(error, "We couldn't update your company settings right now."));
      setErrors(getValidationErrors(error));
    } finally {
      setSaving(false);
    }
  };

  const removeCurrentLogo = () => {
    setRemoveLogo(true);
    setLogoFile(null);
    setLogoPreview(null);
  };

  return (
    <AppShell>
      <div className="space-y-8">
        <section className="rounded-[2rem] border border-white/10 bg-card/80 p-6 shadow-[0_20px_60px_rgba(15,23,42,0.08)] backdrop-blur md:p-8">
          <p className="text-sm uppercase tracking-[0.25em] text-primary/70">Company</p>
          <h1 className="mt-2 text-3xl font-semibold tracking-tight text-foreground">Company settings</h1>
          <p className="mt-2 max-w-3xl text-sm text-muted-foreground">
            This account currently uses one company profile. The details here will appear on your proposals, invoices, and client-facing contract experiences.
          </p>
        </section>

        <section className="rounded-[2rem] border border-white/10 bg-card p-6 md:p-8">
          {loading ? (
            <p className="text-sm text-muted-foreground">Loading company settings...</p>
          ) : (
            <form className="space-y-6" onSubmit={(event) => void handleSubmit(event)}>
              <div className="grid gap-6 xl:grid-cols-[0.8fr_1.2fr]">
                <div className="space-y-4 rounded-3xl border border-white/10 bg-background/60 p-5">
                  <div>
                    <p className="text-sm font-medium text-foreground">Logo</p>
                    <p className="mt-1 text-sm text-muted-foreground">
                      Upload a square or landscape logo for invoices, proposals, and contract presentation.
                    </p>
                  </div>

                  <div className="flex min-h-48 items-center justify-center rounded-3xl border border-dashed border-border bg-card/70 p-4">
                    {logoPreview ? (
                      // eslint-disable-next-line @next/next/no-img-element
                      <img src={logoPreview} alt="Company logo preview" className="max-h-36 max-w-full object-contain" />
                    ) : (
                      <p className="text-center text-sm text-muted-foreground">No logo uploaded yet.</p>
                    )}
                  </div>

                  <input type="file" accept="image/png,image/jpeg,image/webp,image/svg+xml" onChange={handleLogoChange} />

                  {(logoPreview || company.logo_url) ? (
                    <button
                      type="button"
                      onClick={removeCurrentLogo}
                      className="inline-flex items-center justify-center rounded-2xl border border-border px-4 py-2 text-sm font-medium text-foreground transition-colors hover:bg-secondary/60"
                    >
                      Remove logo
                    </button>
                  ) : null}

                  {errors.logo?.map((error) => (
                    <p key={error} className="text-sm text-red-400">{error}</p>
                  ))}
                </div>

                <div className="grid gap-5 md:grid-cols-2">
                  <div className="space-y-2 md:col-span-2">
                    <label htmlFor="company-name" className="text-sm font-medium text-foreground">Company name</label>
                    <input
                      id="company-name"
                      value={company.name}
                      onChange={(event) => updateField("name", event.target.value)}
                      className="w-full rounded-2xl border border-border bg-background px-4 py-3 text-sm outline-none transition-colors focus:border-primary"
                      placeholder="BuildLedger Studio"
                    />
                    {errors.name?.map((error) => (
                      <p key={error} className="text-sm text-red-400">{error}</p>
                    ))}
                  </div>

                  <div className="space-y-2">
                    <label htmlFor="company-email" className="text-sm font-medium text-foreground">Business email</label>
                    <input
                      id="company-email"
                      type="email"
                      value={company.email ?? ""}
                      onChange={(event) => updateField("email", event.target.value)}
                      className="w-full rounded-2xl border border-border bg-background px-4 py-3 text-sm outline-none transition-colors focus:border-primary"
                      placeholder="hello@yourcompany.com"
                    />
                    {errors.email?.map((error) => (
                      <p key={error} className="text-sm text-red-400">{error}</p>
                    ))}
                  </div>

                  <div className="space-y-2">
                    <label htmlFor="company-phone" className="text-sm font-medium text-foreground">Phone</label>
                    <input
                      id="company-phone"
                      value={company.phone ?? ""}
                      onChange={(event) => updateField("phone", event.target.value)}
                      className="w-full rounded-2xl border border-border bg-background px-4 py-3 text-sm outline-none transition-colors focus:border-primary"
                      placeholder="+234..."
                    />
                    {errors.phone?.map((error) => (
                      <p key={error} className="text-sm text-red-400">{error}</p>
                    ))}
                  </div>

                  <div className="space-y-2">
                    <label htmlFor="company-website" className="text-sm font-medium text-foreground">Website</label>
                    <input
                      id="company-website"
                      value={company.website ?? ""}
                      onChange={(event) => updateField("website", event.target.value)}
                      className="w-full rounded-2xl border border-border bg-background px-4 py-3 text-sm outline-none transition-colors focus:border-primary"
                      placeholder="https://yourcompany.com"
                    />
                    {errors.website?.map((error) => (
                      <p key={error} className="text-sm text-red-400">{error}</p>
                    ))}
                  </div>

                  <div className="space-y-2">
                    <label htmlFor="company-tax-id" className="text-sm font-medium text-foreground">Tax ID / Registration</label>
                    <input
                      id="company-tax-id"
                      value={company.tax_id ?? ""}
                      onChange={(event) => updateField("tax_id", event.target.value)}
                      className="w-full rounded-2xl border border-border bg-background px-4 py-3 text-sm outline-none transition-colors focus:border-primary"
                      placeholder="RC / VAT / TIN"
                    />
                    {errors.tax_id?.map((error) => (
                      <p key={error} className="text-sm text-red-400">{error}</p>
                    ))}
                  </div>

                  <div className="space-y-2">
                    <label htmlFor="company-industry" className="text-sm font-medium text-foreground">Industry</label>
                    <input
                      id="company-industry"
                      value={company.industry ?? ""}
                      onChange={(event) => updateField("industry", event.target.value)}
                      className="w-full rounded-2xl border border-border bg-background px-4 py-3 text-sm outline-none transition-colors focus:border-primary"
                      placeholder="Software Development & Digital Consulting"
                    />
                    {errors.industry?.map((error) => (
                      <p key={error} className="text-sm text-red-400">{error}</p>
                    ))}
                  </div>

                  <div className="space-y-2">
                    <label htmlFor="company-currency" className="text-sm font-medium text-foreground">Currency</label>
                    <input
                      id="company-currency"
                      value={company.currency ?? ""}
                      onChange={(event) => updateField("currency", event.target.value.toUpperCase())}
                      className="w-full rounded-2xl border border-border bg-background px-4 py-3 text-sm uppercase outline-none transition-colors focus:border-primary"
                      placeholder="NGN"
                      maxLength={3}
                    />
                    {errors.currency?.map((error) => (
                      <p key={error} className="text-sm text-red-400">{error}</p>
                    ))}
                  </div>

                  <div className="space-y-2 md:col-span-2">
                    <label htmlFor="company-address" className="text-sm font-medium text-foreground">Address</label>
                    <textarea
                      id="company-address"
                      value={company.address ?? ""}
                      onChange={(event) => updateField("address", event.target.value)}
                      rows={5}
                      className="w-full rounded-2xl border border-border bg-background px-4 py-3 text-sm outline-none transition-colors focus:border-primary"
                      placeholder="Office or company address"
                    />
                    {errors.address?.map((error) => (
                      <p key={error} className="text-sm text-red-400">{error}</p>
                    ))}
                  </div>
                </div>
              </div>

              {message ? (
                <p className={`text-sm ${Object.keys(errors).length ? "text-red-400" : "text-emerald-400"}`}>
                  {message}
                </p>
              ) : null}

              <button
                type="submit"
                disabled={saving}
                className="inline-flex items-center justify-center rounded-2xl bg-primary px-5 py-3 text-sm font-medium text-primary-foreground transition-transform hover:translate-y-[-1px] disabled:opacity-60"
              >
                {saving ? "Saving..." : "Save company settings"}
              </button>
            </form>
          )}
        </section>
      </div>
    </AppShell>
  );
}
