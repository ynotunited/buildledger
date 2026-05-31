import type { MetadataRoute } from "next";

const SITE_URL = "https://buildledger.madeitcodes.online";

const PUBLIC_PATHS = [
  "/",
  "/privacy-policy",
  "/terms-of-use",
  "/data-compliance",
  "/ip-infringement",
] as const;

export default function sitemap(): MetadataRoute.Sitemap {
  const now = new Date();

  return PUBLIC_PATHS.map((path) => ({
    url: `${SITE_URL}${path}`,
    lastModified: now,
    changeFrequency: path === "/" ? "weekly" : "monthly",
    priority: path === "/" ? 1 : 0.6,
  }));
}
