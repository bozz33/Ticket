import { NextResponse } from "next/server";

import { mockPlatformConfiguration } from "@/lib/data/mock";

export async function GET() {
  return NextResponse.json({
    settings: {
      branding: {
        platform_name: mockPlatformConfiguration.brandName,
      },
      support: {
        email: mockPlatformConfiguration.supportEmail,
        phone: mockPlatformConfiguration.supportPhone,
      },
      payments: {
        currency: mockPlatformConfiguration.currencyCode,
        methods: mockPlatformConfiguration.paymentMethods,
      },
    },
    feature_flags: mockPlatformConfiguration.featureFlags,
  });
}
