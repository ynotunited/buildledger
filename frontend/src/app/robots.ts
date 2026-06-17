import type { MetadataRoute } from "next";

const SITE_URL = "https://buildledger.madeitcodes.online";

export default function robots(): MetadataRoute.Robots {
  return {
    rules: [
      {
        userAgent: "*",
        allow: [
          "/",
          "/privacy-policy",
          "/terms-of-use",
          "/data-compliance",
          "/ip-infringement",
        ],
        disallow: [
          "/account",
          "/admin",
          "/analytics",
          "/auth",
          "/billing",
          "/clients",
          "/company",
          "/contracts",
          "/dashboard",
          "/forgot-password",
          "/invoices",
          "/issues",
          "/login",
          "/notifications",
          "/payments",
          "/projects",
          "/proposals",
          "/register",
          "/reset-password",
          "/verify-email",
        ],
      },
    ],
    sitemap: `${SITE_URL}/sitemap.xml`,
  };
}
