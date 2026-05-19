"use client";

import { ApplicationProgressSteps } from "./progress/ApplicationProgressSteps";
import { ApplicationProgressSummary } from "./progress/ApplicationProgressSummary";
import type { FormWizardStep } from "./types";

type ApplicationProgressProps = {
  currentStepIndex: number;
  overallCompletedCount: number;
  overallCompletionPercentage: number;
  overallRequiredCount: number;
  visibleFieldCount: number;
  wizardSteps: FormWizardStep[];
  onStepSelect: (stepIndex: number) => void;
};

export function ApplicationProgress({
  currentStepIndex,
  overallCompletedCount,
  overallCompletionPercentage,
  overallRequiredCount,
  visibleFieldCount,
  wizardSteps,
  onStepSelect,
}: ApplicationProgressProps) {
  return (
    <>
      <ApplicationProgressSummary
        overallCompletedCount={overallCompletedCount}
        overallCompletionPercentage={overallCompletionPercentage}
        overallRequiredCount={overallRequiredCount}
        visibleFieldCount={visibleFieldCount}
        wizardStepCount={wizardSteps.length}
      />
      <ApplicationProgressSteps
        currentStepIndex={currentStepIndex}
        onStepSelect={onStepSelect}
        wizardSteps={wizardSteps}
      />
    </>
  );
}
